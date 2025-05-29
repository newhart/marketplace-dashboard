<?php

use App\Http\Controllers\Auth\CustomerRegistrationController;
use Illuminate\Support\Facades\Route;

// Customer registration routes
Route::post('/customer/register', [CustomerRegistrationController::class, 'register'])
    ->middleware('guest')
    ->name('customer.register');

// Social login/registration
Route::post('/customer/social-register', [CustomerRegistrationController::class, 'socialRegister'])
    ->middleware('guest')
    ->name('customer.social.register');
