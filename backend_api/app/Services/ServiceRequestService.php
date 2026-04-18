<?php

namespace App\Services;

use App\Models\Request as ServiceRequest;
use App\Models\Worker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ServiceRequestService
{
    private const COOLDOWN_MINUTES = 30;

    /**
     * Create a request and broadcast it to all available+verified workers
     * of the requested job type (optionally filtered by city).
     *
     * @throws ValidationException
     */
    public function store(int $userId, array $data): ServiceRequest
    {
        // ── Guard 1: no active (pending) request allowed ───────────
        $activeRequest = ServiceRequest::where('user_id', $userId)
            ->whereNull('status')
            ->first();

        if ($activeRequest) {
            throw ValidationException::withMessages([
                'request' => ['You already have an active request. Please wait for it to finish or cancel it first.'],
            ]);
        }

        // ── Guard 2: 30-minute cooldown after last request ─────────
        $lastRequest = ServiceRequest::where('user_id', $userId)
            ->latest()
            ->first();

        if ($lastRequest) {
            $waitUntil = $lastRequest->created_at->addMinutes(self::COOLDOWN_MINUTES);

            if (Carbon::now()->lt($waitUntil)) {
                $minutesLeft = (int) Carbon::now()->diffInMinutes($waitUntil, absolute: false) + 1;

                throw ValidationException::withMessages([
                    'request' => ["You must wait {$minutesLeft} more minute(s) before sending a new request."],
                ]);
            }
        }

        // ── Find eligible workers to broadcast to ──────────────────
        $workers = Worker::query()
            ->where('job_type_id', $data['job_type_id'])
            ->where('is_available', true)
            ->where('is_verified', true)
            ->when(
                ! empty($data['city']),
                fn ($q) => $q->whereHas(
                    'user',
                    fn ($u) => $u->where('city', 'like', '%' . $data['city'] . '%')
                )
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
                'status'      => null,   // null = awaiting response
            ]);

            $pivotData = $workers->mapWithKeys(
                fn ($workerId) => [$workerId => ['status' => 'pending']]
            )->all();

            $request->workers()->attach($pivotData);

            return $request->load(['jobType', 'workers.user']);
        });
    }

    /**
     * Worker accepts the request.
     * Sets requests.status = accepted, records accepted_worker_id,
     * and auto-rejects all other pending pivot rows.
     *
     * @throws ValidationException
     */
    public function accept(ServiceRequest $request, int $workerId): ServiceRequest
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'request' => ['This request has already been responded to.'],
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

        return DB::transaction(function () use ($request, $workerId) {
            // Accept this worker's pivot row
            $request->workers()->updateExistingPivot($workerId, ['status' => 'accepted']);

            // Auto-reject all other pending workers
            $request->workers()
                ->wherePivot('status', 'pending')
                ->each(fn ($w) => $request->workers()->updateExistingPivot($w->id, ['status' => 'rejected']));

            // Lock the request
            $request->update([
                'status'             => 'accepted',
                'accepted_worker_id' => $workerId,
            ]);

            return $request->fresh(['jobType', 'acceptedWorker.user']);
        });
    }

    /**
     * Worker rejects the request (their pivot row only).
     * If all workers have now rejected, sets requests.status = rejected.
     *
     * @throws ValidationException
     */
    public function reject(ServiceRequest $request, int $workerId): ServiceRequest
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'request' => ['This request has already been responded to.'],
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

        // If every worker has now rejected → mark the whole request as rejected
        $anyStillPending = $request->workers()
            ->wherePivot('status', 'pending')
            ->exists();

        if (! $anyStillPending) {
            $request->update(['status' => 'rejected']);
        }

        return $request->fresh(['jobType']);
    }

    /**
     * User cancels their own pending request.
     * Rejects all pending pivot rows and marks the request as rejected.
     *
     * @throws ValidationException
     */
    public function cancel(ServiceRequest $request, int $userId): ServiceRequest
    {
        if ($request->user_id !== $userId) {
            throw ValidationException::withMessages([
                'request' => ['You are not authorised to cancel this request.'],
            ]);
        }

        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'request' => ['This request cannot be cancelled.'],
            ]);
        }

        $request->workers()
            ->wherePivot('status', 'pending')
            ->each(fn ($w) => $request->workers()->updateExistingPivot($w->id, ['status' => 'rejected']));

        $request->update(['status' => 'rejected']);

        return $request->fresh(['jobType']);
    }
}