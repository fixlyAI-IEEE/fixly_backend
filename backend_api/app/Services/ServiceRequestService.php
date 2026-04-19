<?php

namespace App\Services;

use App\Models\Rating;
use App\Models\Request as ServiceRequest;
use App\Models\Worker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ServiceRequestService
{
    private const COOLDOWN_MINUTES = 30;

    // ── USER: Create request & broadcast to workers ────────────────

    public function store(int $userId, array $data): ServiceRequest
    {
        $activeRequest = ServiceRequest::where('user_id', $userId)
            ->whereNull('status')
            ->first();

        if ($activeRequest) {
            throw ValidationException::withMessages([
                'request' => ['You already have an active request. Please wait for it to finish or cancel it.'],
            ]);
        }

        $lastRequest = ServiceRequest::where('user_id', $userId)->latest()->first();

        if ($lastRequest) {
            $waitUntil = $lastRequest->created_at->addMinutes(self::COOLDOWN_MINUTES);

            if (Carbon::now()->lt($waitUntil)) {
                $minutesLeft = (int) Carbon::now()->diffInMinutes($waitUntil, absolute: false) + 1;

                throw ValidationException::withMessages([
                    'request' => ["Please wait {$minutesLeft} more minute(s) before sending a new request."],
                ]);
            }
        }

        $workers = Worker::query()
            ->where('job_type_id', $data['job_type_id'])
            ->where('is_available', true)
            ->where('is_verified', true)
            ->when(
                ! empty($data['city']),
                fn ($q) => $q->whereHas('user', fn ($u) => $u->where('city', 'like', '%' . $data['city'] . '%'))
            )
            ->pluck('id');

        if ($workers->isEmpty()) {
            throw ValidationException::withMessages([
                'job_type_id' => ['No available workers found for this job type in your area.'],
            ]);
        }

        return DB::transaction(function () use ($userId, $data, $workers) {
            $request = ServiceRequest::create([
                'user_id'     => $userId,
                'job_type_id' => $data['job_type_id'],
                'description' => $data['description'] ?? null,
                'city'        => $data['city'] ?? null,
                'status'      => null,
            ]);

            $pivotData = $workers->mapWithKeys(
                fn ($workerId) => [$workerId => ['status' => 'pending']]
            )->all();

            $request->workers()->attach($pivotData);

            return $request->load(['jobType']);
        });
    }

    // ── WORKER: Send offer to user (worker clicks "accept") ────────

    public function workerOffer(ServiceRequest $request, int $workerId): ServiceRequest
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'request' => ['This request is no longer accepting offers.'],
            ]);
        }

        $pivot = $request->workers()
            ->wherePivot('worker_id', $workerId)
            ->wherePivot('status', 'pending')
            ->first();

        if (! $pivot) {
            throw ValidationException::withMessages([
                'request' => ['You are not eligible to respond to this request.'],
            ]);
        }

        $request->workers()->updateExistingPivot($workerId, ['status' => 'offered']);

        return $request->fresh(['jobType', 'offeredWorkers.user']);
    }

    // ── WORKER: Reject the request (their pivot row only) ──────────

    public function workerReject(ServiceRequest $request, int $workerId): ServiceRequest
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'request' => ['This request is no longer active.'],
            ]);
        }

        $pivot = $request->workers()
            ->wherePivot('worker_id', $workerId)
            ->wherePivot('status', 'pending')
            ->first();

        if (! $pivot) {
            throw ValidationException::withMessages([
                'request' => ['You are not eligible to respond to this request.'],
            ]);
        }

        $request->workers()->updateExistingPivot($workerId, ['status' => 'rejected']);

        // If every worker rejected → close the request
        $anyStillActive = $request->workers()
            ->wherePivotIn('status', ['pending', 'offered'])
            ->exists();

        if (! $anyStillActive) {
            $request->update(['status' => 'rejected']);
        }

        return $request->fresh(['jobType']);
    }

    // ── USER: Confirm one worker from those who offered ────────────

    public function confirmWorker(ServiceRequest $request, int $userId, int $workerId): ServiceRequest
    {
        if ($request->user_id !== $userId) {
            throw ValidationException::withMessages([
                'request' => ['Unauthorised.'],
            ]);
        }

        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'request' => ['This request has already been closed.'],
            ]);
        }

        // Make sure this worker actually sent an offer
        $offeredWorker = $request->workers()
            ->wherePivot('worker_id', $workerId)
            ->wherePivot('status', 'offered')
            ->first();

        if (! $offeredWorker) {
            throw ValidationException::withMessages([
                'worker_id' => ['This worker has not sent an offer for your request.'],
            ]);
        }

        return DB::transaction(function () use ($request, $workerId) {
            // Confirm this worker
            $request->workers()->updateExistingPivot($workerId, ['status' => 'accepted']);

            // Reject everyone else (pending or offered)
            $request->workers()
                ->wherePivotIn('status', ['pending', 'offered'])
                ->each(fn ($w) => $request->workers()->updateExistingPivot($w->id, ['status' => 'rejected']));

            $request->update([
                'status'              => 'accepted',
                'accepted_worker_id'  => $workerId,
            ]);

            return $request->fresh(['jobType', 'acceptedWorker.user']);
        });
    }

    // ── USER: Cancel their own pending request ─────────────────────

    public function cancel(ServiceRequest $request, int $userId): ServiceRequest
    {
        if ($request->user_id !== $userId) {
            throw ValidationException::withMessages([
                'request' => ['Unauthorised.'],
            ]);
        }

        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'request' => ['This request cannot be cancelled.'],
            ]);
        }

        $request->workers()
            ->wherePivotIn('status', ['pending', 'offered'])
            ->each(fn ($w) => $request->workers()->updateExistingPivot($w->id, ['status' => 'rejected']));

        $request->update(['status' => 'rejected']);

        return $request->fresh(['jobType']);
    }

    // ── USER: Mark request as completed & submit rating ───────────

    public function completeAndRate(ServiceRequest $request, int $userId, array $data): Rating
    {
        if ($request->user_id !== $userId) {
            throw ValidationException::withMessages([
                'request' => ['Unauthorised.'],
            ]);
        }

        if (! $request->isAccepted()) {
            throw ValidationException::withMessages([
                'request' => ['You can only rate a confirmed request.'],
            ]);
        }

        if (! $request->accepted_worker_id) {
            throw ValidationException::withMessages([
                'request' => ['No confirmed worker found on this request.'],
            ]);
        }

        // Prevent double rating
        $alreadyRated = Rating::where('user_id', $userId)
            ->where('request_id', $request->id)
            ->exists();

        if ($alreadyRated) {
            throw ValidationException::withMessages([
                'request' => ['You have already rated this request.'],
            ]);
        }

        return DB::transaction(function () use ($request, $userId, $data) {
            // Mark request as completed
            $request->update(['status' => 'completed']);

            // Save rating
            $rating = Rating::create([
                'user_id'    => $userId,
                'worker_id'  => $request->accepted_worker_id,
                'request_id' => $request->id,
                'rate'       => $data['rate'],
                'comment'    => $data['comment'] ?? null,
            ]);

            // Recalculate worker's average rating
            $worker = $request->acceptedWorker;
            $worker->rating = Rating::where('worker_id', $worker->id)->avg('rate');
            $worker->save();

            return $rating;
        });
    }
}