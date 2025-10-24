<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\DebugPasswordResetController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\PasswordResetCodeController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [RegisteredUserController::class, 'store'])
    ->middleware('guest')
    ->name('register');

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest')
    ->name('login');

// Ancien système de réinitialisation par token (gardé pour compatibilité)
Route::post('/forgot-password-old', [PasswordResetLinkController::class, 'store'])
    ->middleware('guest')
    ->name('password.email.old');

Route::post('/reset-password-old', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.store.old');

// Nouveau système de réinitialisation par code à 6 chiffres
Route::post('/forgot-password', [PasswordResetCodeController::class, 'sendResetCode'])
    ->middleware(['guest', 'throttle:5,1']) // Max 5 tentatives par minute
    ->name('password.code.send');

Route::post('/verify-reset-code', [PasswordResetCodeController::class, 'verifyResetCode'])
    ->middleware(['guest', 'throttle:10,1']) // Max 10 tentatives par minute
    ->name('password.code.verify');

Route::post('/reset-password-with-code', [PasswordResetCodeController::class, 'resetPasswordWithCode'])
    ->middleware(['guest', 'throttle:3,1']) // Max 3 tentatives par minute
    ->name('password.reset.code');

// Routes de debug (à supprimer en production)
Route::get('/debug/password-reset/codes', [DebugPasswordResetController::class, 'debugCodes'])
    ->name('debug.password.codes');

Route::post('/debug/password-reset/token', [DebugPasswordResetController::class, 'debugToken'])
    ->name('debug.password.token');

Route::post('/debug/password-reset/generate', [DebugPasswordResetController::class, 'generateTestCode'])
    ->name('debug.password.generate');

Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['auth', 'signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/socialie/{provider}', [SocialiteController::class, 'redirectToProvider']);
Route::get('/socialie/{provider}/callback', [SocialiteController::class, 'handleProviderCallback']);
