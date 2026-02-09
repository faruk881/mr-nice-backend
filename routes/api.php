<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordUpdateController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth Group
Route::prefix('auth')->group(function () {
    Route::post('/register', [RegisterController::class, 'store']);
    Route::post('/login', [LoginController::class, 'store']);
    Route::post('/logout', [LogoutController::class, 'destroy'])->middleware('auth:sanctum');

    // Email verification
    Route::post('/email/verify', [EmailVerificationController::class, 'verify']);
    Route::post('/email/resend', [EmailVerificationController::class, 'resend']);

    // Forgot Password (Public)
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store']);
    Route::post('/forgot-password/verify', [ForgotPasswordController::class, 'verify']);
});

// Profile Group (Private)
Route::middleware('auth:sanctum')->group(function () {
    Route::put('/profile/password', [PasswordUpdateController::class, 'update']);
});
