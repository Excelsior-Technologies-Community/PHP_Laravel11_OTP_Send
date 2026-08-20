<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientAuthController;


/*
|--------------------------------------------------------------------------
| CLIENT REGISTRATION
|--------------------------------------------------------------------------
*/

Route::get(
    'client/register',
    [ClientAuthController::class, 'registerForm']
)->name('client.register.form');

Route::post(
    'client/register',
    [ClientAuthController::class, 'register']
)->name('client.register');


/*
|--------------------------------------------------------------------------
| CLIENT LOGIN
|--------------------------------------------------------------------------
*/

Route::get(
    'client/login',
    [ClientAuthController::class, 'loginForm']
)->name('client.login.form');


Route::post(
    'client/login/send-otp',
    [ClientAuthController::class, 'sendOtp']
)->name('client.login.sendOtp');


/*
|--------------------------------------------------------------------------
| RESEND OTP
|--------------------------------------------------------------------------
*/

Route::post(
    'client/login/resend-otp',
    [ClientAuthController::class, 'resendOtp']
)->name('client.login.resendOtp');


/*
|--------------------------------------------------------------------------
| OTP VERIFICATION
|--------------------------------------------------------------------------
*/

Route::get(
    'client/login/verify-otp',
    [ClientAuthController::class, 'otpForm']
)->name('client.otp.form');


Route::post(
    'client/login/verify-otp',
    [ClientAuthController::class, 'verifyOtp']
)->name('client.otp.verify');


/*
|--------------------------------------------------------------------------
| AUTHENTICATED CLIENT ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('auth:client')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/dashboard',
        [ClientAuthController::class, 'dashboard']
    )->name('client.dashboard');


    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    Route::post(
        'client/logout',
        [ClientAuthController::class, 'logout']
    )->name('client.logout');


    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */

    Route::get(
        'client/profile/edit',
        [ClientAuthController::class, 'editProfile']
    )->name('client.profile.edit');


    Route::put(
        'client/profile',
        [ClientAuthController::class, 'updateProfile']
    )->name('client.profile.update');


    /*
    |--------------------------------------------------------------------------
    | CHANGE PASSWORD
    |--------------------------------------------------------------------------
    */

    Route::get(
        'client/change-password',
        [ClientAuthController::class, 'changePasswordForm']
    )->name('client.password.change.form');


    Route::post(
        'client/change-password',
        [ClientAuthController::class, 'changePassword']
    )->name('client.password.change');


    /*
    |--------------------------------------------------------------------------
    | OTP HISTORY
    |--------------------------------------------------------------------------
    */

    Route::get(
        'client/otp-history',
        [ClientAuthController::class, 'otpHistory']
    )->name('client.otp.history');
});


/*
|--------------------------------------------------------------------------
| FORGOT PASSWORD
|--------------------------------------------------------------------------
*/

Route::get(
    'client/forgot-password',
    [ClientAuthController::class, 'forgotPasswordForm']
)->name('client.forgot-password.form');


Route::post(
    'client/forgot-password',
    [ClientAuthController::class, 'sendResetOtp']
)->name('client.forgot-password.send');


/*
|--------------------------------------------------------------------------
| RESET PASSWORD OTP
|--------------------------------------------------------------------------
*/

Route::get(
    'client/reset/verify-otp/{token}',
    [ClientAuthController::class, 'verifyResetOtpForm']
)->name('client.reset.verify-otp');


Route::post(
    'client/reset/verify-otp/{token}',
    [ClientAuthController::class, 'verifyResetOtp']
)->name('client.reset.verify-otp.submit');


/*
|--------------------------------------------------------------------------
| RESET PASSWORD
|--------------------------------------------------------------------------
*/

Route::get(
    'client/reset/password/{token}',
    [ClientAuthController::class, 'resetPasswordForm']
)->name('client.reset.password.form');


Route::post(
    'client/reset/password/{token}',
    [ClientAuthController::class, 'resetPassword']
)->name('client.reset.password');


/*
|--------------------------------------------------------------------------
| HOME
|--------------------------------------------------------------------------
*/

Route::get('/', function () {

    if (Auth::guard('client')->check()) {
        return redirect()->route('client.dashboard');
    }

    return view('welcome');
});


/*
|--------------------------------------------------------------------------
| AUTH ALIASES
|--------------------------------------------------------------------------
*/

Route::get('login', function () {

    return redirect()->route(
        'client.login.form'
    );
})->name('login');


Route::get('register', function () {

    return redirect()->route(
        'client.register.form'
    );
})->name('register');
