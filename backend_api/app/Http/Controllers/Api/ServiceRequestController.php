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

    // ────────────────────────────────────────────────────────────────
    // USER — view their own requests
    // GET /api/requests
    // ────────────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $requests = ServiceRequest::with(['jobType', 'workers.user'])
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

    // ────────────────────────────────────────────────────────────────
    // USER — create (broadcast) a new request
    // POST /api/requests
    // ────────────────────────────────────────────────────────────────
    public function store(StoreRequestRequest $request): JsonResponse
    {
        $serviceRequest = $this->service->store(
            $request->user()->id,
            $request->validated()
        );

        return response()->json([
            'message' => 'Request sent to available workers.',
            'data'    => new ServiceRequestResource($serviceRequest),
        ], 201);
    }

    // ────────────────────────────────────────────────────────────────
    // USER — view a single request
    // GET /api/requests/{id}
    // ────────────────────────────────────────────────────────────────
    public function show(Request $request, int $id): JsonResponse
    {
        $serviceRequest = ServiceRequest::with(['jobType', 'workers.user'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'data' => new ServiceRequestResource($serviceRequest),
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // USER — cancel a request
    // PATCH /api/requests/{id}/cancel
    // ────────────────────────────────────────────────────────────────
    public function cancel(Request $request, int $id): JsonResponse
    {
        $serviceRequest = ServiceRequest::findOrFail($id);
        $updated = $this->service->cancel($serviceRequest, $request->user()->id);

        return response()->json([
            'message' => 'Request cancelled.',
            'data'    => new ServiceRequestResource($updated),
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // WORKER — view requests broadcast to them
    // GET /api/worker/requests
    // ────────────────────────────────────────────────────────────────
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

    // ────────────────────────────────────────────────────────────────
    // WORKER — accept a request
    // PATCH /api/worker/requests/{id}/accept
    // ────────────────────────────────────────────────────────────────
    public function accept(Request $request, int $id): JsonResponse
    {
        $worker = $request->user()->worker;

        if (! $worker) {
            return response()->json(['message' => 'Worker profile not found.'], 404);
        }

        $serviceRequest = ServiceRequest::findOrFail($id);
        $updated = $this->service->accept($serviceRequest, $worker->id);

        return response()->json([
            'message' => 'Request accepted.',
            'data'    => new ServiceRequestResource($updated),
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // WORKER — reject a request
    // PATCH /api/worker/requests/{id}/reject
    // ────────────────────────────────────────────────────────────────
    public function reject(Request $request, int $id): JsonResponse
    {
        $worker = $request->user()->worker;

        if (! $worker) {
            return response()->json(['message' => 'Worker profile not found.'], 404);
        }

        $serviceRequest = ServiceRequest::findOrFail($id);
        $updated = $this->service->reject($serviceRequest, $worker->id);

        return response()->json([
            'message' => 'Request rejected.',
            'data'    => new ServiceRequestResource($updated),
        ]);
    }
}