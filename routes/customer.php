<?php

use App\Http\Controllers\Auth\CustomerRegistrationController;
use App\Http\Controllers\Api\CustomerProfileController;
use Illuminate\Support\Facades\Route;

// Customer registration routes
Route::post('/customer/register', [CustomerRegistrationController::class, 'register'])
    ->middleware('guest')
    ->name('customer.register');

// Social login/registration
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
    });
});
