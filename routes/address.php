<?php

use App\Http\Controllers\Api\AddressController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Routes pour les adresses des clients
    Route::prefix('customer')->group(function () {
        Route::get('/addresses', [AddressController::class, 'index']);
        Route::post('/addresses', [AddressController::class, 'store']);
        Route::get('/addresses/{id}', [AddressController::class, 'show']);
        Route::put('/addresses/{id}', [AddressController::class, 'update']);
        Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);
        Route::patch('/addresses/{id}/set-default', [AddressController::class, 'setDefault']);
    });
});
