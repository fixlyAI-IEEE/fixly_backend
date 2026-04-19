<?php

namespace App\Services;

use App\Models\User;
use App\Models\Worker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(private readonly OtpService $otpService) {}

    public function registerUser(array $data): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'phone'    => $data['phone'],
            'password' => $data['password'],
            'role'     => 'user',
            'city'     => $data['city']  ?? null,
            'areas'    => $data['areas'] ?? null,
        ]);

        // Send OTP to verify phone
        $this->otpService->generate($user->phone);

        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function registerWorker(array $data): array
    {
        $profilePicturePath = null;
        if (isset($data['profile_picture'])) {
            $profilePicturePath = $data['profile_picture']->store('avatars', 'public');
        }

        $user = User::create([
            'name'            => $data['name'],
            'phone'           => $data['phone'],
            'password'        => $data['password'],
            'role'            => 'worker',
            'city'            => $data['city']  ?? null,
            'areas'           => $data['areas'] ?? null,
            'profile_picture' => $profilePicturePath,
        ]);

       $worker = Worker::create([
            'user_id'      => $user->id,
            'job_type_id'  => $data['job_type_id'],
            'working_days' => $data['working_days'] ?? null,
            'avg_price'    => $data['avg_price'] ?? null,
        ]);
        // Send OTP to verify phone
        $this->otpService->generate($user->phone);

        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user,'worker' => $worker, 'token' => $token];
    }

    public function login(string $phone, string $password, ?string $role = null): array
    {
        $query = User::where('phone', $phone);

        if ($role) {
            $query->where('role', $role);
        }

        $user = $query->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Block unverified accounts
        if (is_null($user->phone_verified_at)) {
            // Resend OTP so they can verify
            $this->otpService->generate($user->phone);

            throw ValidationException::withMessages([
                'phone' => ['Your phone number is not verified. A new OTP has been sent.'],
            ]);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function verifyPhone(string $phone, string $otp): User
    {
        $user = User::where('phone', $phone)->firstOrFail();

        if (! is_null($user->phone_verified_at)) {
            throw ValidationException::withMessages([
                'phone' => ['This phone number is already verified.'],
            ]);
        }

        $this->otpService->verify($phone, $otp);
        $this->otpService->consume($phone);

        $user->update(['phone_verified_at' => now()]);

        return $user->fresh();
    }

    public function resendOtp(string $phone): void
    {
        $user = User::where('phone', $phone)->firstOrFail();

        if (! is_null($user->phone_verified_at)) {
            throw ValidationException::withMessages([
                'phone' => ['This phone number is already verified.'],
            ]);
        }

        $this->otpService->generate($phone);
    }

    public function updateProfilePicture(User $user, $file): User
    {
        // Delete old picture if exists
        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        $path = $file->store('avatars', 'public');
        $user->update(['profile_picture' => $path]);

        return $user->fresh();
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}