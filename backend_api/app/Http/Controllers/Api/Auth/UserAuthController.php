<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserAuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterUserRequest $request): JsonResponse
    {
        $result = $this->authService->registerUser($request->validated());

        return response()->json([
            'message' => 'Registration successful. Please verify your phone number.',
            'data'    => [
                'user'  => new UserResource($result['user']),
                'token' => $result['token'],
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->phone,
            $request->password,
        );

        return response()->json([
            'message' => 'Login successful.',
            'data'    => [
                'user'  => new UserResource($result['user']),
                'token' => $result['token'],
            ],
        ]);
    }

    // POST /api/auth/verify-phone
    public function verifyPhone(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'exists:users,phone'],
            'otp'   => ['required', 'string', 'size:6'],
        ]);

        $user = $this->authService->verifyPhone(
            $request->phone,
            $request->otp
        );

        return response()->json([
            'message' => 'Phone verified successfully.',
            'data'    => new UserResource($user),
        ]);
    }

    // POST /api/auth/resend-otp
    public function resendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'exists:users,phone'],
        ]);

        $this->authService->resendOtp($request->phone);

        return response()->json([
            'message' => 'OTP sent successfully.',
        ]);
    }

    // POST /api/auth/profile-picture
    public function updateProfilePicture(Request $request): JsonResponse
    {
        $request->validate([
            'profile_picture' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $user = $this->authService->updateProfilePicture(
            $request->user(),
            $request->file('profile_picture')
        );

        return response()->json([
            'message' => 'Profile picture updated.',
            'data'    => new UserResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($request->user()->load('worker.jobType')),
        ]);
    }
}