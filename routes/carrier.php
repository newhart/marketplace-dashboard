<?php

use App\Http\Controllers\Api\TransporterController;
use Illuminate\Support\Facades\Route;

/**
 * Module Carrier – parcours transporteur.
 * Pas d'inscription : création des comptes côté admin.
 * Toutes les routes exigent Bearer token (auth:sanctum).
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [TransporterController::class, 'carrierProfile']);
    Route::put('/profile', [TransporterController::class, 'carrierUpdateProfile']);
    Route::get('/dashboard', [TransporterController::class, 'carrierDashboard']);

    Route::get('/orders', [TransporterController::class, 'carrierOrders']);
    Route::get('/orders/{id}', [TransporterController::class, 'carrierOrderDetail']);
    Route::post('/orders/{id}/accept', [TransporterController::class, 'carrierAcceptOrder']);
    Route::post('/orders/{id}/items/{itemId}/validate', [TransporterController::class, 'carrierValidateOrderItem']);
    Route::post('/orders/{id}/status', [TransporterController::class, 'carrierOrderStatus']);
});
