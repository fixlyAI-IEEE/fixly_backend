<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterWorkerRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\WorkerResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class WorkerAuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    /**
     * POST /api/auth/worker/register
     */
    public function register(RegisterWorkerRequest $request): JsonResponse
    {
        $result = $this->authService->registerWorker($request->validated());

        return response()->json([
            'message' => 'Worker registration successful.',
            'data'    => [
                'user'   => new UserResource($result['user']),
                'worker' => new WorkerResource($result['worker']),
                'token'  => $result['token'],
            ],
        ], 201);
    }

    /**
     * POST /api/auth/worker/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->phone,
            $request->password,
            role: 'worker'
        );

        return response()->json([
            'message' => 'Login successful.',
            'data'    => [
                'user'  => new UserResource($result['user']),
                'token' => $result['token'],
            ],
        ]);
    }
}