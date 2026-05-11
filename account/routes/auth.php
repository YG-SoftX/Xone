<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\TwoFactorAuthenticatedSessionController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Account type selection

    Route::post('register/check-email', [RegisteredUserController::class, 'checkEmail'])
        ->name('register.check-email');
    

    // Individual registration
    Route::get('register/individual', [RegisteredUserController::class, 'showIndividualRegistration'])
        ->name('register.individual');
    Route::post('register/individual', [RegisteredUserController::class, 'registerIndividual']);

    // Business registration
    Route::get('register/business', [RegisteredUserController::class, 'showBusinessRegistration'])
        ->name('register.business');
    Route::post('register/business', [RegisteredUserController::class, 'registerBusiness']);

    // Legacy register route (redirect to individual registration)
    Route::get('register', function () {
        return redirect()->route('register.individual');
    });
    Route::post('register', function () {
        return redirect()->route('register.individual');
    });

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('2fa', [TwoFactorAuthenticatedSessionController::class, 'create'])
        ->name('2fa');

    Route::post('2fa', [TwoFactorAuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    // Email verification
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // Password management
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    // Business domain verification
    Route::post('business/{business}/verify-domain', [RegisteredUserController::class, 'verifyBusinessDomain'])
        ->name('business.verify-domain');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    // Security Checkpoint (OTP)
    Route::get('verify-security', [\App\Http\Controllers\Auth\OtpVerificationController::class, 'show'])
        ->name('otp.verify');
    Route::post('verify-security', [\App\Http\Controllers\Auth\OtpVerificationController::class, 'verify'])
        ->name('otp.verify.submit');
});
