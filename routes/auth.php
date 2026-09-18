<?php

use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Admin\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Admin\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Admin\Auth\NewPasswordController;
use App\Http\Controllers\Admin\Auth\PasswordController;
use App\Http\Controllers\Admin\Auth\PasswordResetLinkController;
use App\Http\Controllers\Admin\Auth\RegisteredUserController;
use App\Http\Controllers\Admin\Auth\VerifyEmailController;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest Authentication Routes
|--------------------------------------------------------------------------
| Routes accessible by unauthenticated users (Guests):
| Registration, Login, Forgot Password, and Password Reset.
*/
Route::middleware(['check', HandleInertiaRequests::class])->group(function () {
    // User Registration
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    // User Login
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Forgot Password (Request Reset Link)
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    // Reset Password with Token
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

/*
|--------------------------------------------------------------------------
| Authenticated User Routes
|--------------------------------------------------------------------------
| Routes that require an active user session:
| Email Verification, Confirm Password, Password Update, and Logout.
*/
Route::middleware(['auth', HandleInertiaRequests::class])->group(function () {
    // Email Verification Notice & Verification Link Handler
    Route::get('verify-email', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware('throttle:6,1')->name('verification.send');

    // Password Confirmation (for sensitive actions)
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    // Update Authenticated User Password
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    // Sign Out / Logout (Handles both Inertia POST and fallback browser GET)
    Route::match(['get', 'post'], 'logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Two-Factor & OTP Verification Routes
|--------------------------------------------------------------------------
| Routes handling OTP verification flow during registration or login.
*/
Route::middleware(['check', HandleInertiaRequests::class])->group(function () {
    // OTP Verification
    Route::get('verify/otp/page', [RegisteredUserController::class, 'otpPage'])->name('verify.otp.page');
    Route::post('verify/otp', [RegisteredUserController::class, 'otpVerify'])->name('verify.otp');

    // OTP Resend
    Route::get('verify/otp/resend/page', [RegisteredUserController::class, 'otpResendPage'])->name('verify.otp.resend.page');
    Route::post('verify/otp/resend', [RegisteredUserController::class, 'otpResend'])->name('verify.otp.resend');
});
