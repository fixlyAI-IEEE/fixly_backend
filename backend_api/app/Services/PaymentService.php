<?php

namespace App\Services;

use App\Models\PaymentCycle;
use App\Models\Worker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    private const FEE_PER_JOB    = 15.00;
    private const JOBS_PER_CYCLE = 5;

    /**
     * Called every time a job is completed.
     * Increments counter, creates/updates cycle, blocks worker at cycle end.
     */
    public function recordCompletedJob(Worker $worker): void
    {
        DB::transaction(function () use ($worker) {
            $worker->increment('completed_jobs_count');
            $worker->refresh();

            $cycleNumber   = (int) ceil($worker->completed_jobs_count / self::JOBS_PER_CYCLE);
            $jobsInCycle   = $worker->completed_jobs_count % self::JOBS_PER_CYCLE === 0
                ? self::JOBS_PER_CYCLE
                : $worker->completed_jobs_count % self::JOBS_PER_CYCLE;

            // Create cycle on first job of each cycle
            $cycle = PaymentCycle::firstOrCreate(
                ['worker_id' => $worker->id, 'cycle_number' => $cycleNumber],
                [
                    'completed_jobs'   => 0,
                    'amount_due'       => self::FEE_PER_JOB * self::JOBS_PER_CYCLE,
                    'status'           => 'pending',
                    'cycle_started_at' => now(),
                ]
            );

            $cycle->update(['completed_jobs' => $jobsInCycle]);

            // Every 5 jobs — close cycle and block worker
            if ($worker->completed_jobs_count % self::JOBS_PER_CYCLE === 0) {
                $cycle->update(['cycle_ended_at' => now()]);

                // Update worker totals and block
                $worker->update([
                    'total_amount_due'  => $worker->total_amount_due + (self::FEE_PER_JOB * self::JOBS_PER_CYCLE),
                    'is_payment_pending' => true,
                    'is_available'      => false,
                ]);
            }
        });
    }

    /**
     * Worker uploads payment proof screenshot.
     */
    public function uploadProof(Worker $worker, $file): PaymentCycle
    {
        // Find the latest unpaid cycle
        $cycle = PaymentCycle::where('worker_id', $worker->id)
            ->whereIn('status', ['pending', 'rejected'])
            ->latest()
            ->first();

        if (! $cycle) {
            throw ValidationException::withMessages([
                'proof' => ['No pending payment cycle found.'],
            ]);
        }

        // Delete old proof if exists
        if ($cycle->proof_image) {
            Storage::disk('public')->delete($cycle->proof_image);
        }

        $path = $file->store('payment_proofs', 'public');

        $cycle->update([
            'proof_image'       => $path,
            'proof_uploaded_at' => now(),
            'status'            => 'proof_uploaded',
        ]);

        return $cycle->fresh();
    }

    /**
     * Admin approves payment — unblocks worker.
     */
    public function approvePayment(PaymentCycle $cycle): PaymentCycle
    {
        if (! $cycle->isProofUploaded()) {
            throw ValidationException::withMessages([
                'cycle' => ['This cycle has no uploaded proof to approve.'],
            ]);
        }

        DB::transaction(function () use ($cycle) {
            $cycle->update([
                'status'      => 'paid',
                'amount_paid' => $cycle->amount_due,
                'paid_at'     => now(),
            ]);

            $worker = $cycle->worker;
            $worker->update([
                'total_amount_paid'  => $worker->total_amount_paid + $cycle->amount_due,
                'is_payment_pending' => false,
                'is_available'       => true,
            ]);
        });

        return $cycle->fresh();
    }

    /**
     * Admin rejects proof — worker must re-upload.
     */
    public function rejectPayment(PaymentCycle $cycle, string $reason = null): PaymentCycle
    {
        if (! $cycle->isProofUploaded()) {
            throw ValidationException::withMessages([
                'cycle' => ['This cycle has no uploaded proof to reject.'],
            ]);
        }

        $cycle->update(['status' => 'rejected']);

        return $cycle->fresh();
    }
}