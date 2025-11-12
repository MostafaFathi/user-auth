<?php

use Illuminate\Support\Facades\Route;
use MostafaFathi\UserAuth\Http\Controllers\SsoAuthController;

Route::middleware('web')->group(function () {
    // SSO Routes
    Route::get('/auth/sso/redirect', [SsoAuthController::class, 'redirectToSso'])->name('sso.redirect');
    Route::get('/auth/sso/callback', [SsoAuthController::class, 'ssoCallback'])->name('sso.callback');
    
    // OTP Routes
    Route::get('/auth/verify', [SsoAuthController::class, 'otpVerifyPage'])->name('otpVerifyPage');
    Route::post('/auth/otp/request', [SsoAuthController::class, 'requestOtp'])->name('otp.request');
    Route::post('/auth/otp/verify', [SsoAuthController::class, 'verifyOtp'])->name('otp.verify');
    
    // Logout
    Route::post('/auth/logout', [SsoAuthController::class, 'logout'])->name('logout');
});
