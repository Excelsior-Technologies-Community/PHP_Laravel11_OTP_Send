<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientAuthController;

// ------------------ CLIENT REGISTRATION ROUTES ------------------
// Display client registration form
Route::get('client/register', [ClientAuthController::class, 'registerForm'])->name('client.register.form');

// Handle client registration form submission
Route::post('client/register', [ClientAuthController::class, 'register'])->name('client.register');

// ------------------ CLIENT LOGIN ROUTES ------------------
// Display client login form (email input only)
Route::get('client/login', [ClientAuthController::class, 'loginForm'])->name('client.login.form');

// Send OTP to client's email after email submission
Route::post('client/login/send-otp', [ClientAuthController::class, 'sendOtp'])->name('client.login.sendOtp');

// ------------------ CLIENT OTP VERIFICATION ROUTES ------------------
// Display OTP verification form
Route::get('client/login/verify-otp', [ClientAuthController::class, 'otpForm'])->name('client.otp.form');

// Verify OTP and complete login process
Route::post('client/login/verify-otp', [ClientAuthController::class, 'verifyOtp'])->name('client.otp.verify');

// ------------------ CLIENT LOGOUT ROUTE ------------------
// Handle client logout (POST for security)
Route::post('client/logout', [ClientAuthController::class, 'logout'])->name('client.logout');

// ------------------ HOME ROUTE ------------------
// Default homepage (after successful login)
Route::get('/', function () {
    return view('welcome');
});
