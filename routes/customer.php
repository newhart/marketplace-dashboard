<?php

use App\Http\Controllers\Auth\CustomerRegistrationController;
use App\Http\Controllers\Auth\CustomerSocialAuthController;
use App\Http\Controllers\Api\CustomerProfileController;
use Illuminate\Support\Facades\Route;

// Customer registration routes
Route::post('/customer/register', [CustomerRegistrationController::class, 'register'])
    ->middleware('guest')
    ->name('customer.register');

// Social authentication (improved)
Route::post('/customer/social-auth', [CustomerSocialAuthController::class, 'socialAuth'])
    ->middleware(['guest', 'throttle:10,1'])
    ->name('customer.social.auth');

// Legacy social registration (kept for compatibility)
Route::post('/customer/social-register', [CustomerRegistrationController::class, 'socialRegister'])
    ->middleware('guest')
    ->name('customer.social.register');

// Customer profile management routes (authenticated)
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('customer')->group(function () {
        // Profile management
        Route::get('/profile', [CustomerProfileController::class, 'show']);
        Route::put('/profile', [CustomerProfileController::class, 'update']);
        Route::post('/profile/change-password', [CustomerProfileController::class, 'changePassword']);
        Route::delete('/profile', [CustomerProfileController::class, 'deleteAccount']);
        
        // Social account management
        Route::post('/link-social-account', [CustomerSocialAuthController::class, 'linkSocialAccount']);
        Route::delete('/unlink-social-account', [CustomerSocialAuthController::class, 'unlinkSocialAccount']);
    });
});
