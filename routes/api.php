<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SiteController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function (): void {
    Route::prefix('auth')->controller(AuthController::class)->group(function (): void {
        Route::post('signup', 'signup');
        Route::post('login', 'login');
        Route::post('forgot-password', 'forgotPassword');
        Route::post('password/verify-otp', 'verifyPasswordOtp');
        Route::post('password/resend-otp', 'resendPasswordOtp');
        Route::post('password/reset', 'resetPassword');
        Route::post('email-login', 'emailLogin');
        Route::post('email-login/verify-otp', 'verifyEmailLoginOtp');
        Route::post('email-login/resend-otp', 'resendEmailLoginOtp');
    });

    Route::middleware('api.token')->group(function (): void {
        Route::get('customers/search', [CustomerController::class, 'search']);
        Route::apiResource('customers', CustomerController::class);

        Route::get('sites/search', [SiteController::class, 'search']);
        Route::apiResource('sites', SiteController::class);
    });
});
