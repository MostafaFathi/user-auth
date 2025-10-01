<?php

use Illuminate\Support\Facades\Route;
use MostafaFathi\UserAuth\Http\Controllers\AuthController;

Route::middleware('web')->group(function () {
    // SSO Routes
    Route::get('/auth/sso/redirect', [AuthController::class, 'redirectToSso'])->name('sso.redirect');
    Route::get('/auth/sso/callback', [AuthController::class, 'ssoCallback'])->name('sso.callback');
    
    // OTP Routes
    Route::post('/auth/otp/request', [AuthController::class, 'requestOtp'])->name('otp.request');
    Route::post('/auth/otp/verify', [AuthController::class, 'verifyOtp'])->name('otp.verify');
    
    // Logout
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('logout');
});
