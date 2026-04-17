<?php

namespace App\Services;

use App\Models\User;
use App\Models\Worker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register a regular user and return a Sanctum token.
     */
    public function registerUser(array $data): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'phone'    => $data['phone'],
            'password' => $data['password'],   // cast: hashed
            'role'     => 'user',
            'city'     => $data['city']  ?? null,
            'areas'    => $data['areas'] ?? null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Register a worker (creates user row + worker profile row).
     */
    public function registerWorker(array $data): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'phone'    => $data['phone'],
            'password' => $data['password'],
            'role'     => 'worker',
            'city'     => $data['city']  ?? null,
            'areas'    => $data['areas'] ?? null,
        ]);

        $worker = Worker::create([
            'user_id'     => $user->id,
            'job_type_id' => $data['job_type_id'],
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user, 'worker' => $worker, 'token' => $token];
    }

    /**
     * Authenticate by phone + password, optionally scoped to a role.
     */
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

        // Revoke previous tokens to enforce single-session per device
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Revoke the current access token.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}