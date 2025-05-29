<?php

use App\Http\Controllers\Auth\MerchantRegistrationController;
use App\Http\Controllers\Admin\MerchantApprovalController;
use Illuminate\Support\Facades\Route;

// Public merchant registration routes
Route::post('/merchant/register', [MerchantRegistrationController::class, 'register'])
    ->middleware('guest')
    ->name('merchant.register');

// Merchant account status - requires authentication
Route::get('/merchant/status', [MerchantRegistrationController::class, 'status'])
    ->middleware(['auth:sanctum', 'verified'])
    ->name('merchant.status');

// Admin routes for merchant approval - requires admin role
Route::middleware(['auth:sanctum', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin/merchants/pending', [MerchantApprovalController::class, 'pendingMerchants'])
        ->name('admin.merchants.pending');
    
    Route::post('/admin/merchants/{id}/approve', [MerchantApprovalController::class, 'approve'])
        ->name('admin.merchants.approve');
    
    Route::post('/admin/merchants/{id}/reject', [MerchantApprovalController::class, 'reject'])
        ->name('admin.merchants.reject');
});
