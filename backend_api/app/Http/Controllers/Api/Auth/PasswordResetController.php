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
use App\Services\SmsService;
class PasswordResetController extends Controller
{
    public function __construct(private readonly OtpService $otpService) {}

  public function sendOtp(SendOtpRequest $request): JsonResponse
{
    $otp = $this->otpService->generate($request->phone);

    app(SmsService::class)->send(
        $request->phone,
        "Your OTP code is: $otp"
    );

    return response()->json([
        'message' => 'OTP sent successfully.',
    ]);
}

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $this->otpService->verify($request->phone, $request->otp);

        return response()->json([
            'message' => 'OTP verified. You may now reset your password.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->otpService->consume($request->phone);

        User::where('phone', $request->phone)
            ->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'message' => 'Password reset successfully. Please log in.',
        ]);
    }
}