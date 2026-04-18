<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkerFilterRequest;
use App\Http\Resources\WorkerResource;
use App\Models\Worker;
use App\Services\WorkerService;
use Illuminate\Http\JsonResponse;

class WorkerController extends Controller
{
    public function __construct(private readonly WorkerService $workerService) {}

    /**
     * GET /api/workers
     *
     * Query params:
     *   job_type_id  integer  optional
     *   city         string   optional
     *   areas        string   optional
     *   min_rating   numeric  optional (0-5)
     *   per_page     integer  optional (default 15, max 50)
     */
    public function index(WorkerFilterRequest $request): JsonResponse
    {
        $workers = $this->workerService->listing($request->validated());

        return response()->json([
            'data' => WorkerResource::collection($workers),
            'meta' => [
                'current_page' => $workers->currentPage(),
                'last_page'    => $workers->lastPage(),
                'per_page'     => $workers->perPage(),
                'total'        => $workers->total(),
            ],
        ]);
    }

    /**
     * GET /api/workers/{id}
     */
    public function show(int $id): JsonResponse
    {
        $worker = Worker::with(['user', 'jobType'])
            ->where('is_verified', true)
            ->findOrFail($id);

        return response()->json([
            'data' => new WorkerResource($worker),
        ]);
    }
}