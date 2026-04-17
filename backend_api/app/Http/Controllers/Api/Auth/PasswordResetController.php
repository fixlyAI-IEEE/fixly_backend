<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class PasswordResetController extends Controller
{
    public function __construct(private readonly OtpService $otpService) {}

    /**
     * POST /api/auth/password/send-otp
     *
     * Step 1 — generate OTP and (later) send via SMS.
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $otp = $this->otpService->generate($request->phone);

            if (!User::where('phone', $request->phone)->exists()) {
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }
    }

    /**
     * POST /api/auth/password/verify-otp
     *
     * Step 2 — verify the OTP is correct and not expired.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $this->otpService->verify($request->phone, $request->otp);

        return response()->json([
            'message' => 'OTP verified. You may now reset your password.',
        ]);
    }

    /**
     * POST /api/auth/password/reset
     *
     * Step 3 — consume the verified OTP and set new password.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        // Will throw ValidationException if OTP is invalid / not verified
        $this->otpService->consume($request->phone, $request->otp);

        User::where('phone', $request->phone)
                ->update(['password' => $request->password]);
        return response()->json([
            'message' => 'Password reset successfully. Please log in.',
        ]);
    }
}