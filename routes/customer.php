<?php

use App\Http\Controllers\Auth\CustomerRegistrationController;
use App\Http\Controllers\Auth\CustomerSocialAuthController;
use App\Http\Controllers\Api\CustomerProfileController;
use App\Http\Controllers\Api\ProductReviewController;
use App\Http\Controllers\Api\PublicBoutiqueController;
use Illuminate\Support\Facades\Route;

// Public boutique listing (no authentication required)
Route::get('/boutiques', [PublicBoutiqueController::class, 'index'])
    ->name('public.boutiques.index');

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

// Customer profile & reviews management routes (authenticated)
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

        // Product reviews (avis clients par produit)
        Route::get('/products/{product}/reviews', [ProductReviewController::class, 'index']);
        Route::post('/products/{product}/reviews', [ProductReviewController::class, 'store']);
    });
});
