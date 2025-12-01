<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\HealthCheckController;
use App\Http\Controllers\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/search', [ProductController::class, 'searchProduct']);
    Route::get('/category/{category}', [ProductController::class, 'getByCategory']);
    Route::get('/suggest', [ProductController::class, 'suggestProduct']);
    Route::get('/{product}/similar', [ProductController::class, 'getSimilarProducts']);
    Route::get('{product}', [ProductController::class, 'show']);
});

Route::prefix('categories')->group(function () {
    Route::get('/rays', [CategoryController::class, 'rays']);
});

// Health check endpoints
Route::get('/ping', [HealthCheckController::class, 'ping']);
Route::get('/health', [HealthCheckController::class, 'check']);
Route::get('/server-info', [HealthCheckController::class, 'serverInfo']);

// Promotional Banners
Route::get('/promotional-banners', [App\Http\Controllers\Api\PromotionalBannerController::class, 'index']);


Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

require __DIR__ . '/auth.php';
require __DIR__ . '/merchant.php';
require __DIR__ . '/customer.php';
require __DIR__ . '/order.php';
require __DIR__ . '/address.php';
