<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientAuthController;
use App\Http\Controllers\AdminOtpHistoryController;

// ------------------ CLIENT REGISTRATION ROUTES ------------------
Route::get('client/register', [ClientAuthController::class, 'registerForm'])->name('client.register.form');
Route::post('client/register', [ClientAuthController::class, 'register'])->name('client.register');

// ------------------ CLIENT LOGIN ROUTES ------------------
Route::get('client/login', [ClientAuthController::class, 'loginForm'])->name('client.login.form');
Route::post('client/login/send-otp', [ClientAuthController::class, 'sendOtp'])->name('client.login.sendOtp');

// ------------------ CLIENT RESEND OTP ROUTE ------------------
Route::post('client/login/resend-otp', [ClientAuthController::class, 'resendOtp'])->name('client.login.resendOtp');

// ------------------ CLIENT OTP VERIFICATION ROUTES ------------------
Route::get('client/login/verify-otp', [ClientAuthController::class, 'otpForm'])->name('client.otp.form');
Route::post('client/login/verify-otp', [ClientAuthController::class, 'verifyOtp'])->name('client.otp.verify');

// ------------------ CLIENT LOGOUT ROUTE ------------------
Route::post('client/logout', [ClientAuthController::class, 'logout'])->name('client.logout');

// ------------------ CLIENT DASHBOARD ROUTE ------------------
Route::middleware('auth:client')->get('/dashboard', [ClientAuthController::class, 'dashboard'])->name('client.dashboard');

// ------------------ PASSWORD RESET ROUTES ------------------
Route::get('client/forgot-password', [ClientAuthController::class, 'forgotPasswordForm'])->name('client.forgot-password.form');
Route::post('client/forgot-password', [ClientAuthController::class, 'sendResetOtp'])->name('client.forgot-password.send');
Route::get('client/reset/verify-otp/{token}', [ClientAuthController::class, 'verifyResetOtpForm'])->name('client.reset.verify-otp');
Route::post('client/reset/verify-otp/{token}', [ClientAuthController::class, 'verifyResetOtp'])->name('client.reset.verify-otp.submit');
Route::get('client/reset/password/{token}', [ClientAuthController::class, 'resetPasswordForm'])->name('client.reset.password.form');
Route::post('client/reset/password/{token}', [ClientAuthController::class, 'resetPassword'])->name('client.reset.password');

// ------------------ ADMIN OTP HISTORY ------------------

Route::get(
    '/admin/otp-history',
    [AdminOtpHistoryController::class, 'index']
)->name('admin.otp-history.index');


// ------------------ HOME ROUTE ------------------
Route::get('/', function () {
    if (Auth::guard('client')->check()) {
        return redirect()->route('client.dashboard');
    }
    return view('welcome');
});

// ------------------ AUTH ALIASES ------------------
// Redirect standard Laravel guest redirect to custom OTP login
Route::get('login', function() {
    return redirect()->route('client.login.form');
})->name('login');
Route::get('register', function() {
    return redirect()->route('client.register.form');
})->name('register');
