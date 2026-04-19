<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(private readonly ChatService $service) {}

    // POST /api/chat
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $result = $this->service->sendMessage(
            $request->user()->id,
            $request->string('message')
        );

        return response()->json($result);
    }

    // GET /api/chat/history
    public function history(Request $request): JsonResponse
    {
        $history = $this->service->getHistory($request->user()->id);

        return response()->json([
            'data' => $history->map(fn ($chat) => [
                'id'         => $chat->id,
                'message'    => $chat->message,
                'response'   => $chat->response,
                'job_type'   => $chat->jobType?->name,
                'created_at' => $chat->created_at->toDateTimeString(),
            ]),
        ]);
    }

    // GET /api/chat/workers/{jobTypeId}
    // User clicks "Show available workers" button
    public function workersByJobType(Request $request, int $jobTypeId): JsonResponse
    {
        $workers = \App\Models\Worker::with('user')
            ->where('job_type_id', $jobTypeId)
            ->where('is_available', true)
            ->where('is_verified', true)
            ->orderByDesc('rating')
            ->get()
            ->map(fn ($worker) => [
                'worker_id' => $worker->id,
                'name'      => $worker->user?->name,
                'rating'    => $worker->rating,
                'city'      => $worker->user?->city,
            ]);

        return response()->json(['data' => $workers]);
    }
}