<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/search', [ProductController::class, 'searchProduct']);
    Route::get('{product}', [ProductController::class, 'show']);
    Route::get('/category/{category}', [ProductController::class, 'getByCategory']);
});

Route::prefix('categories')->group(function () {
    Route::get('/rays', [CategoryController::class, 'rays']);
});

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

require __DIR__ . '/auth.php';
