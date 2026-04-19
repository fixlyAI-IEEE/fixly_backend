<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequestRequest;
use App\Http\Resources\ServiceRequestResource;
use App\Models\Request as ServiceRequest;
use App\Services\ServiceRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    public function __construct(private readonly ServiceRequestService $service) {}

    // ── USER: list their requests ──────────────────────────────────
    // GET /api/requests

    public function index(Request $request): JsonResponse
    {
        $requests = ServiceRequest::with(['jobType', 'acceptedWorker.user'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => ServiceRequestResource::collection($requests),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page'    => $requests->lastPage(),
                'total'        => $requests->total(),
            ],
        ]);
    }

    // ── USER: create request ───────────────────────────────────────
    // POST /api/requests

    public function store(StoreRequestRequest $request): JsonResponse
    {
        $serviceRequest = $this->service->store(
            $request->user()->id,
            $request->validated()
        );
        $serviceRequest->load(['jobType']);

        return response()->json([
            'message' => 'Request sent to available workers.',
            'data'    => new ServiceRequestResource($serviceRequest),
        ], 201);
    }

    // ── USER: view single request ──────────────────────────────────
    // GET /api/requests/{id}

    public function show(Request $request, int $id): JsonResponse
    {
        $serviceRequest = ServiceRequest::with(['jobType', 'acceptedWorker.user', 'offeredWorkers.user'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'data' => new ServiceRequestResource($serviceRequest),
        ]);
    }

    // ── USER: see workers who offered on their request ─────────────
    // GET /api/requests/{id}/offers

    public function offers(Request $request, int $id): JsonResponse
    {
        $serviceRequest = ServiceRequest::with(['offeredWorkers.user'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'data' => $serviceRequest->offeredWorkers->map(fn ($worker) => [
                'worker_id' => $worker->id,
                'name'      => $worker->user->name,
                'rating'    => $worker->rating,
                'job_type'  => $worker->jobType?->name,
            ]),
        ]);
    }

    // ── USER: confirm a worker from offers ─────────────────────────
    // PATCH /api/requests/{id}/confirm

    public function confirm(Request $request, int $id): JsonResponse
    {
        $request->validate(['worker_id' => ['required', 'integer', 'exists:workers,id']]);

        $serviceRequest = ServiceRequest::findOrFail($id);

        $updated = $this->service->confirmWorker(
            $serviceRequest,
            $request->user()->id,
            $request->integer('worker_id')
        );

        return response()->json([
            'message' => 'Worker confirmed. Job is now accepted.',
            'data'    => new ServiceRequestResource($updated),
        ]);
    }

    // ── USER: cancel their request ─────────────────────────────────
    // PATCH /api/requests/{id}/cancel

    public function cancel(Request $request, int $id): JsonResponse
    {
        $serviceRequest = ServiceRequest::findOrFail($id);
        $updated = $this->service->cancel($serviceRequest, $request->user()->id);

        return response()->json([
            'message' => 'Request cancelled.',
            'data'    => new ServiceRequestResource($updated),
        ]);
    }

    // ── USER: complete request + submit rating ─────────────────────
    // POST /api/requests/{id}/rate

    public function rate(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'rate'    => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $serviceRequest = ServiceRequest::findOrFail($id);

        $rating = $this->service->completeAndRate(
            $serviceRequest,
            $request->user()->id,
            $request->only('rate', 'comment')
        );

        return response()->json([
            'message' => 'Request completed and rating submitted.',
            'data'    => [
                'rate'    => $rating->rate,
                'comment' => $rating->comment,
            ],
        ], 201);
    }

    // ── WORKER: inbox (requests broadcast to them) ─────────────────
    // GET /api/worker/requests

 public function workerInbox(Request $request): JsonResponse
{
    $worker = $request->user()->worker;

    if (! $worker) {
        return response()->json(['message' => 'Worker profile not found.'], 404);
    }

    $requests = ServiceRequest::with(['jobType', 'user'])
        ->join('request_workers', 'requests.id', '=', 'request_workers.request_id')
        ->where('request_workers.worker_id', $worker->id)
        ->select('requests.*', 'request_workers.status as pivot_status')
        ->latest('requests.created_at')
        ->paginate(15);

    return response()->json([
        'data' => ServiceRequestResource::collection($requests),
        'meta' => [
            'current_page' => $requests->currentPage(),
            'last_page'    => $requests->lastPage(),
            'total'        => $requests->total(),
        ],
    ]);
}

    // ── WORKER: send offer (worker accepts) ────────────────────────
    // PATCH /api/worker/requests/{id}/offer

    public function workerOffer(Request $request, int $id): JsonResponse
    {
        $worker = $request->user()->worker;

        if (! $worker) {
            return response()->json(['message' => 'Worker profile not found.'], 404);
        }

        $serviceRequest = ServiceRequest::findOrFail($id);
        $updated = $this->service->workerOffer($serviceRequest, $worker->id);

        return response()->json([
            'message' => 'Offer sent to user.',
            'data'    => new ServiceRequestResource($updated),
        ]);
    }

    // ── WORKER: reject request ─────────────────────────────────────
    // PATCH /api/worker/requests/{id}/reject

    public function workerReject(Request $request, int $id): JsonResponse
    {
        $worker = $request->user()->worker;

        if (! $worker) {
            return response()->json(['message' => 'Worker profile not found.'], 404);
        }

        $serviceRequest = ServiceRequest::findOrFail($id);
        $updated = $this->service->workerReject($serviceRequest, $worker->id);

        return response()->json([
            'message' => 'Request rejected.',
            'data'    => new ServiceRequestResource($updated),
        ]);
    }
}