<?php

namespace App\Services;

use App\Models\PasswordResetOtp;
use Illuminate\Validation\ValidationException;

class OtpService
{
    private const OTP_TTL_MINUTES = 10;

    /**
     * Generate a 6-digit OTP and persist it.
     * Returns the OTP so the caller can hand it to an SMS provider later.
     */
    public function generate(string $phone): string
    {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PasswordResetOtp::updateOrCreate(
            ['phone' => $phone],
            [
                'otp'         => $otp,
                'is_verified' => false,
                'expires_at'  => now()->addMinutes(self::OTP_TTL_MINUTES),
                'created_at'  => now(),
            ]
        );

        // SmsService::send($phone, "Your Fixly OTP is: {$otp}");

        return $otp;
    }

    /**
     * Verify the OTP is correct, not expired, and not already used.
     * Marks it as verified on success so it can be consumed once in reset.
     *
     * @throws ValidationException
     */
    public function verify(string $phone, string $otp): void
    {
        $record = PasswordResetOtp::where('phone', $phone)->first();

        if (! $record) {
            throw ValidationException::withMessages([
                'otp' => ['No OTP was requested for this phone number.'],
            ]);
        }

        if ($record->isExpired()) {
            throw ValidationException::withMessages([
                'otp' => ['OTP has expired. Please request a new one.'],
            ]);
        }

        if ($record->otp !== $otp) {
            throw ValidationException::withMessages([
                'otp' => ['The OTP is incorrect.'],
            ]);
        }

        // Mark verified so reset step can trust it without re-checking
        $record->update(['is_verified' => true]);
    }

    /**
     * Confirm the OTP is in a verified state and delete it (one-time use).
     *
     * @throws ValidationException
     */
public function consume(string $phone): void
{
    $record = PasswordResetOtp::where('phone', $phone)->first();

    if (! $record || ! $record->is_verified || $record->isExpired()) {
        throw ValidationException::withMessages([
            'otp' => ['Invalid or expired OTP. Please restart the process.'],
        ]);
    }

    $record->delete();
}
}