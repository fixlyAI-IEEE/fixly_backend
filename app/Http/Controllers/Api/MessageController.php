<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Models\Message;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    public function store(StoreMessageRequest $request): JsonResponse
    {
        $message = Message::create($request->validated());

        return response()->json([
            'message' => 'Message sent successfully.',
            'data'    => $message,
        ], 201);
    }
}