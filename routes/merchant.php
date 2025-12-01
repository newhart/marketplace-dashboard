<?php

use App\Http\Controllers\Auth\MerchantRegistrationController;
use App\Http\Controllers\Admin\MerchantApprovalController;
use App\Http\Controllers\Merchant\DashboardController;
use App\Http\Controllers\Merchant\ProductController;
use App\Http\Controllers\Merchant\OrderController;
use Illuminate\Support\Facades\Route;

// Public merchant registration routes
Route::post('/merchant/register', [MerchantRegistrationController::class, 'register'])
    ->middleware('guest')
    ->name('merchant.register');

// Merchant account status - requires authentication
Route::get('/merchant/status', [MerchantRegistrationController::class, 'status'])
    ->middleware(['auth:sanctum', 'verified'])
    ->name('merchant.status');

// Merchant dashboard routes - requires authentication and merchant role
Route::middleware(['auth:sanctum', 'verified', 'role:merchant'])->prefix('merchant')->name('merchant.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Product management
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{id}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');

    // Order management
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
});

// Admin routes for merchant approval - requires admin role
Route::middleware(['auth:sanctum', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin/merchants/pending', [MerchantApprovalController::class, 'pendingMerchants'])
        ->name('admin.merchants.pending');

    Route::post('/admin/merchants/{id}/approve', [MerchantApprovalController::class, 'approve'])
        ->name('admin.merchants.approve');

    Route::post('/admin/merchants/{id}/reject', [MerchantApprovalController::class, 'reject'])
        ->name('admin.merchants.reject');
});
