<?php

use App\Http\Controllers\Api\TransporterController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('transporter')->group(function () {
    // Commandes à livrer
    Route::get('/orders', [TransporterController::class, 'ordersToDeliver']);
    Route::get('/orders/{id}', [TransporterController::class, 'orderDetail']);
    Route::post('/orders/{id}/validate-delivery', [TransporterController::class, 'validateDelivery']);

    // Profil transporteur
    Route::get('/profile', [TransporterController::class, 'profile']);
    Route::put('/profile', [TransporterController::class, 'updateProfile']);
});
