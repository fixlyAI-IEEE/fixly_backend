<?php

use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\UserAuthController;
use App\Http\Controllers\Api\Auth\WorkerAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth — Public routes (no token required)
|--------------------------------------------------------------------------
*/

// ── User auth ──────────────────────────────────────────────────────────
Route::prefix('auth/user')->group(function () {
    Route::post('register', [UserAuthController::class, 'register']);
    Route::post('login',    [UserAuthController::class, 'login']);
});

// ── Worker auth ────────────────────────────────────────────────────────
Route::prefix('auth/worker')->group(function () {
    Route::post('register', [WorkerAuthController::class, 'register']);
    Route::post('login',    [WorkerAuthController::class, 'login']);
});

// ── Forgot password (OTP flow) ─────────────────────────────────────────
Route::prefix('auth/password')->group(function () {
    Route::post('send-otp',   [PasswordResetController::class, 'sendOtp']);
    Route::post('verify-otp', [PasswordResetController::class, 'verifyOtp']);
    Route::post('reset',      [PasswordResetController::class, 'resetPassword']);
});

/*
|--------------------------------------------------------------------------
| Auth — Protected routes (Sanctum token required)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::post('auth/logout', [UserAuthController::class, 'logout']);
    Route::get('auth/me',      [UserAuthController::class, 'me']);

    /*
    |----------------------------------------------------------------------
    | Future modules go here — example:
    |----------------------------------------------------------------------
    | Route::apiResource('requests', RequestController::class);
    | Route::apiResource('ratings',  RatingController::class);
    */
});