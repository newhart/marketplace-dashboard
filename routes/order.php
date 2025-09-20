<?php

use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Route pour créer une commande
    Route::post('/orders', [OrderController::class, 'store']);
    
    // Routes pour les clients
    Route::prefix('api/customer')->group(function () {
        Route::get('/orders', [OrderController::class, 'customerOrders']);
        Route::get('/orders/{id}', [OrderController::class, 'customerOrderDetail']);
    });
    
    // Routes pour les marchands
    Route::prefix('/merchant')->group(function () {
        Route::get('/orders', [OrderController::class, 'merchantOrders']);
        Route::get('/orders/{id}', [OrderController::class, 'merchantOrderDetail']);
        Route::delete('/orders/{id}', [OrderController::class, 'cancelOrder']);
    });
    
    // Route pour générer une facture
    Route::get('/orders/{id}/invoice', [OrderController::class, 'generateInvoice']);
});
