<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentCycle;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $service) {}

    // GET /api/worker/payment-cycles
    public function index(Request $request): JsonResponse
    {
        $worker = $request->user()->worker;

        if (! $worker) {
            return response()->json(['message' => 'Worker profile not found.'], 404);
        }

        $cycles = PaymentCycle::where('worker_id', $worker->id)
            ->orderByDesc('cycle_number')
            ->get()
            ->map(fn ($cycle) => [
                'cycle_number'      => $cycle->cycle_number,
                'completed_jobs'    => $cycle->completed_jobs,
                'amount_due'        => $cycle->amount_due,
                'amount_paid'       => $cycle->amount_paid,
                'status'            => $cycle->status,
                'proof_uploaded_at' => $cycle->proof_uploaded_at?->toDateTimeString(),
                'paid_at'           => $cycle->paid_at?->toDateTimeString(),
                'cycle_started_at'  => $cycle->cycle_started_at?->toDateTimeString(),
                'cycle_ended_at'    => $cycle->cycle_ended_at?->toDateTimeString(),
                'proof_image'       => $cycle->proof_image
                    ? asset('storage/' . $cycle->proof_image)
                    : null,
            ]);

        return response()->json([
            'data' => $cycles,
            'summary' => [
                'total_completed_jobs' => $worker->completed_jobs_count,
                'total_amount_due'     => $worker->total_amount_due,
                'total_amount_paid'    => $worker->total_amount_paid,
                'total_remaining'      => $worker->total_amount_due - $worker->total_amount_paid,
                'is_blocked'           => $worker->isBlocked(),
            ],
        ]);
    }

    // POST /api/worker/payment-proof
    public function uploadProof(Request $request): JsonResponse
    {
        $request->validate([
            'proof' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $worker = $request->user()->worker;

        if (! $worker) {
            return response()->json(['message' => 'Worker profile not found.'], 404);
        }

        $cycle = $this->service->uploadProof($worker, $request->file('proof'));

        return response()->json([
            'message' => 'Payment proof uploaded. Waiting for admin approval.',
            'data'    => [
                'cycle_number'      => $cycle->cycle_number,
                'status'            => $cycle->status,
                'proof_uploaded_at' => $cycle->proof_uploaded_at->toDateTimeString(),
                'proof_image'       => asset('storage/' . $cycle->proof_image),
            ],
        ]);
    }
}