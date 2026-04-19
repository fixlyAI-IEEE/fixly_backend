<?php

use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\UserAuthController;
use App\Http\Controllers\Api\Auth\WorkerAuthController;
use App\Http\Controllers\Api\WorkerController;
use App\Http\Controllers\Api\ServiceRequestController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MessageController;
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

// ── Workers listing — public (anyone can browse workers) ───────────────
Route::get('workers',      [WorkerController::class, 'index']);
Route::get('workers/{id}', [WorkerController::class, 'show']);

//Messages Through Landing Page 
Route::post('messages', [MessageController::class, 'store']);
/*
|--------------------------------------------------------------------------
| Protected routes (Sanctum token required)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // ── Auth ───────────────────────────────────────────────────────
    Route::post('auth/logout', [UserAuthController::class, 'logout']);
    Route::get('auth/me',      [UserAuthController::class, 'me']);

   Route::get('/requests', [ServiceRequestController::class, 'index']);
    Route::post('/requests', [ServiceRequestController::class, 'store']);
    Route::get('/requests/{id}', [ServiceRequestController::class, 'show']);
    Route::get('/requests/{id}/offers', [ServiceRequestController::class, 'offers']);
    Route::patch('/requests/{id}/confirm', [ServiceRequestController::class, 'confirm']);
    Route::patch('/requests/{id}/cancel', [ServiceRequestController::class, 'cancel']);
    Route::post('/requests/{id}/rate', [ServiceRequestController::class, 'rate']);

    // ── Worker  ───────────────────────────────────
    Route::prefix('worker')->group(function () {
        Route::get('/requests', [ServiceRequestController::class, 'workerInbox']);
        Route::patch('/requests/{id}/offer', [ServiceRequestController::class, 'workerOffer']);
        Route::patch('/requests/{id}/reject', [ServiceRequestController::class, 'workerReject']);
    });

});