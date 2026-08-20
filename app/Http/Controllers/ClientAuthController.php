<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\OtpHistory;
use App\Models\OtpRequestLimit;
use App\Models\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ClientAuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | OTP RATE LIMIT SETTINGS
    |--------------------------------------------------------------------------
    */

    private const MAX_OTP_REQUESTS = 3;

    private const RATE_LIMIT_MINUTES = 10;


    // ------------------ REGISTRATION FORM ------------------

    public function registerForm()
    {
        return view('client.registration');
    }


    // ------------------ REGISTER NEW CLIENT ------------------

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:clients',
            'password' => 'required|min:6|confirmed',
            'phone' => 'nullable',
        ]);

        $client = Client::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        Mail::raw(
            "Hello {$client->name}, your registration is successful!",
            function ($msg) use ($client) {
                $msg->to($client->email)
                    ->subject("Welcome to Our App");
            }
        );

        Auth::guard('client')->login($client);

        return redirect()
            ->route('client.dashboard')
            ->with('success', 'Registration successful! Welcome.');
    }


    // ------------------ LOGIN FORM ------------------

    public function loginForm()
    {
        return view('client.login');
    }


    // ------------------ SEND OTP ------------------

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $client = Client::where('email', $request->email)->first();

        if (!$client) {

            /*
            |--------------------------------------------------------------------------
            | Log failed OTP request for unknown email
            |--------------------------------------------------------------------------
            */

            OtpHistory::create([
                'channel' => 'email',
                'recipient' => $request->email,
                'action' => 'send',
                'status' => 'failed',
                'ip_address' => $request->ip(),
                'message' => 'Client not found.',
            ]);

            return back()->withErrors([
                'email' => 'Client not found.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK OTP RATE LIMIT
        |--------------------------------------------------------------------------
        */

        $rateLimit = OtpRequestLimit::where('channel', 'email')
            ->where('recipient', $client->email)
            ->first();


        /*
        |--------------------------------------------------------------------------
        | FIRST OTP REQUEST
        |--------------------------------------------------------------------------
        */

        if (!$rateLimit) {

            $rateLimit = OtpRequestLimit::create([
                'channel' => 'email',
                'recipient' => $client->email,
                'ip_address' => $request->ip(),
                'request_count' => 1,
                'window_started_at' => now(),
            ]);
        } else {

            /*
            |--------------------------------------------------------------------------
            | Calculate rate-limit window
            |--------------------------------------------------------------------------
            */

            $windowEndsAt = $rateLimit->window_started_at
                ->copy()
                ->addMinutes(self::RATE_LIMIT_MINUTES);


            /*
            |--------------------------------------------------------------------------
            | Reset expired rate-limit window
            |--------------------------------------------------------------------------
            */

            if (now()->greaterThanOrEqualTo($windowEndsAt)) {

                $rateLimit->update([
                    'request_count' => 1,
                    'ip_address' => $request->ip(),
                    'window_started_at' => now(),
                ]);
            } else {

                /*
                |--------------------------------------------------------------------------
                | BLOCK AFTER 3 REQUESTS
                |--------------------------------------------------------------------------
                */

                if ($rateLimit->request_count >= self::MAX_OTP_REQUESTS) {

                    $remainingSeconds = now()->diffInSeconds(
                        $windowEndsAt,
                        false
                    );

                    $remainingMinutes = max(
                        1,
                        (int) ceil($remainingSeconds / 60)
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Save blocked request in OTP history
                    |--------------------------------------------------------------------------
                    */

                    OtpHistory::create([
                        'channel' => 'email',
                        'recipient' => $client->email,
                        'action' => 'send',
                        'status' => 'blocked',
                        'ip_address' => $request->ip(),
                        'message' => "OTP request limit exceeded. Try again in {$remainingMinutes} minute(s).",
                    ]);


                    return back()
                        ->withErrors([
                            'email' => "Too many OTP requests. Please try again in {$remainingMinutes} minute(s).",
                        ])
                        ->withInput();
                }


                /*
                |--------------------------------------------------------------------------
                | Increase request count
                |--------------------------------------------------------------------------
                */

                $rateLimit->increment('request_count');

                $rateLimit->update([
                    'ip_address' => $request->ip(),
                ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Generate OTP
        |--------------------------------------------------------------------------
        */

        $otp = random_int(100000, 999999);


        /*
        |--------------------------------------------------------------------------
        | Store OTP
        |--------------------------------------------------------------------------
        */

        $client->login_otp = $otp;

        $client->login_otp_expires_at = now()->addMinutes(5);

        $client->login_otp_attempts = 0;

        $client->login_otp_locked_until = null;

        $client->save();


        /*
        |--------------------------------------------------------------------------
        | Send OTP Email
        |--------------------------------------------------------------------------
        */

        $subject = "Your Login OTP";

        $message = "Your OTP is: {$otp}\nThis OTP will expire in 5 minutes.";

        try {

            Mail::raw(
                $message,
                function ($mail) use ($client, $subject) {
                    $mail->to($client->email)
                        ->subject($subject);
                }
            );

        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Log failed OTP email
            |--------------------------------------------------------------------------
            */

            OtpHistory::create([
                'channel' => 'email',
                'recipient' => $client->email,
                'action' => 'send',
                'status' => 'failed',
                'ip_address' => $request->ip(),
                'message' => 'Failed to send OTP email: ' . $e->getMessage(),
            ]);

            return back()
                ->withErrors([
                    'email' => 'Unable to send OTP email. Please try again.',
                ])
                ->withInput();
        }


        /*
        |--------------------------------------------------------------------------
        | Save Successful OTP Request
        |--------------------------------------------------------------------------
        */

        OtpHistory::create([
            'channel' => 'email',
            'recipient' => $client->email,
            'action' => 'send',
            'status' => 'success',
            'ip_address' => $request->ip(),
            'message' => 'OTP sent successfully.',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Store Client ID In Session
        |--------------------------------------------------------------------------
        */

        session([
            'client_login_id' => $client->id,
        ]);


        return redirect()
            ->route('client.otp.form')
            ->with(
                'success',
                'OTP has been sent to your email!'
            );
    }


    // ------------------ RESEND OTP ------------------

    public function resendOtp(Request $request)
    {
        $client = Client::find(
            session('client_login_id')
        );


        if (!$client) {

            return redirect()
                ->route('client.login.form')
                ->withErrors([
                    'otp' => 'Session expired.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK OTP RATE LIMIT
        |--------------------------------------------------------------------------
        */

        $rateLimit = OtpRequestLimit::where('channel', 'email')
            ->where('recipient', $client->email)
            ->first();


        /*
        |--------------------------------------------------------------------------
        | FIRST RESEND REQUEST
        |--------------------------------------------------------------------------
        */

        if (!$rateLimit) {

            $rateLimit = OtpRequestLimit::create([
                'channel' => 'email',
                'recipient' => $client->email,
                'ip_address' => $request->ip(),
                'request_count' => 1,
                'window_started_at' => now(),
            ]);
        } else {

            $windowEndsAt = $rateLimit->window_started_at
                ->copy()
                ->addMinutes(self::RATE_LIMIT_MINUTES);


            /*
            |--------------------------------------------------------------------------
            | Reset expired window
            |--------------------------------------------------------------------------
            */

            if (now()->greaterThanOrEqualTo($windowEndsAt)) {

                $rateLimit->update([
                    'request_count' => 1,
                    'ip_address' => $request->ip(),
                    'window_started_at' => now(),
                ]);
            } else {

                /*
                |--------------------------------------------------------------------------
                | Block after 3 requests
                |--------------------------------------------------------------------------
                */

                if ($rateLimit->request_count >= self::MAX_OTP_REQUESTS) {

                    $remainingSeconds = now()->diffInSeconds(
                        $windowEndsAt,
                        false
                    );

                    $remainingMinutes = max(
                        1,
                        (int) ceil($remainingSeconds / 60)
                    );


                    OtpHistory::create([
                        'channel' => 'email',
                        'recipient' => $client->email,
                        'action' => 'send',
                        'status' => 'blocked',
                        'ip_address' => $request->ip(),
                        'message' => "OTP resend limit exceeded. Try again in {$remainingMinutes} minute(s).",
                    ]);


                    return back()
                        ->withErrors([
                            'otp' => "Too many OTP requests. Please try again in {$remainingMinutes} minute(s).",
                        ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Increase request count
                |--------------------------------------------------------------------------
                */

                $rateLimit->increment('request_count');

                $rateLimit->update([
                    'ip_address' => $request->ip(),
                ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Generate New OTP
        |--------------------------------------------------------------------------
        */

        $otp = random_int(100000, 999999);


        $client->login_otp = $otp;

        $client->login_otp_expires_at = now()->addMinutes(5);

        $client->login_otp_attempts = 0;

        $client->login_otp_locked_until = null;

        $client->save();


        /*
        |--------------------------------------------------------------------------
        | Send New OTP
        |--------------------------------------------------------------------------
        */

        $subject = "Your Login OTP (Resent)";

        $message = "Your new OTP is: {$otp}\nThis OTP will expire in 5 minutes.";

        try {

            Mail::raw(
                $message,
                function ($mail) use ($client, $subject) {
                    $mail->to($client->email)
                        ->subject($subject);
                }
            );

        } catch (\Throwable $e) {

            OtpHistory::create([
                'channel' => 'email',
                'recipient' => $client->email,
                'action' => 'send',
                'status' => 'failed',
                'ip_address' => $request->ip(),
                'message' => 'Failed to send OTP email: ' . $e->getMessage(),
            ]);

            return back()
                ->withErrors([
                    'otp' => 'Unable to send OTP email. Please try again.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Save Successful Resend
        |--------------------------------------------------------------------------
        */

        OtpHistory::create([
            'channel' => 'email',
            'recipient' => $client->email,
            'action' => 'send',
            'status' => 'success',
            'ip_address' => $request->ip(),
            'message' => 'OTP resent successfully.',
        ]);


        return back()
            ->with(
                'success',
                'New OTP has been sent to your email!'
            );
    }


    // ------------------ OTP VERIFICATION FORM ------------------

    public function otpForm()
    {
        $client = Client::find(
            session('client_login_id')
        );


        if (!$client) {

            return redirect()
                ->route('client.login.form')
                ->withErrors([
                    'otp' => 'Session expired.',
                ]);
        }


        return view(
            'client.verify-otp',
            [
                'expires_at' => $client->login_otp_expires_at,
                'client' => $client,
            ]
        );
    }


    // ------------------ VERIFY OTP & LOGIN ------------------

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);


        $client = Client::find(
            session('client_login_id')
        );


        if (!$client) {

            return redirect()
                ->route('client.login.form')
                ->withErrors([
                    'otp' => 'Session expired.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Check OTP Account Lock
        |--------------------------------------------------------------------------
        */

        if (
            $client->login_otp_locked_until &&
            now()->lt($client->login_otp_locked_until)
        ) {

            $remaining = now()->diffInSeconds(
                $client->login_otp_locked_until
            );

            $minutes = max(
                1,
                ceil($remaining / 60)
            );


            /*
            |--------------------------------------------------------------------------
            | Save blocked verification history
            |--------------------------------------------------------------------------
            */

            OtpHistory::create([
                'channel' => 'email',
                'recipient' => $client->email,
                'action' => 'verify',
                'status' => 'blocked',
                'ip_address' => $request->ip(),
                'message' => "Account temporarily locked. Try again in {$minutes} minute(s).",
            ]);


            return back()
                ->withErrors([
                    'otp' => "Account temporarily locked. Try again in {$minutes} minute(s).",
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Check OTP Exists
        |--------------------------------------------------------------------------
        */

        if (!$client->login_otp) {

            OtpHistory::create([
                'channel' => 'email',
                'recipient' => $client->email,
                'action' => 'verify',
                'status' => 'failed',
                'ip_address' => $request->ip(),
                'message' => 'OTP expired or not found.',
            ]);


            return back()
                ->withErrors([
                    'otp' => 'OTP expired or not found.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Check OTP Expiration
        |--------------------------------------------------------------------------
        */

        if (
            !$client->login_otp_expires_at ||
            now()->gt($client->login_otp_expires_at)
        ) {

            OtpHistory::create([
                'channel' => 'email',
                'recipient' => $client->email,
                'action' => 'verify',
                'status' => 'failed',
                'ip_address' => $request->ip(),
                'message' => 'OTP expired.',
            ]);


            return back()
                ->withErrors([
                    'otp' => 'OTP expired.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Check Wrong OTP
        |--------------------------------------------------------------------------
        */

        if ((string) $client->login_otp !== (string) $request->otp) {

            $client->login_otp_attempts =
                ($client->login_otp_attempts ?? 0) + 1;


            /*
            |--------------------------------------------------------------------------
            | Maximum 3 Verification Attempts
            |--------------------------------------------------------------------------
            */

            if ($client->login_otp_attempts >= 3) {

                $client->login_otp_locked_until =
                    now()->addMinutes(15);

                $client->save();


                OtpHistory::create([
                    'channel' => 'email',
                    'recipient' => $client->email,
                    'action' => 'verify',
                    'status' => 'blocked',
                    'ip_address' => $request->ip(),
                    'message' => 'Too many failed OTP attempts. Account locked for 15 minutes.',
                ]);


                return back()
                    ->withErrors([
                        'otp' => 'Too many failed attempts. Account locked for 15 minutes.',
                    ]);
            }


            $client->save();


            $attemptMessage =
                'Invalid OTP. Attempts: ' .
                $client->login_otp_attempts .
                '/3';


            /*
            |--------------------------------------------------------------------------
            | Save Failed Verification
            |--------------------------------------------------------------------------
            */

            OtpHistory::create([
                'channel' => 'email',
                'recipient' => $client->email,
                'action' => 'verify',
                'status' => 'failed',
                'ip_address' => $request->ip(),
                'message' => $attemptMessage,
            ]);


            return back()
                ->withErrors([
                    'otp' => $attemptMessage,
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | OTP Successfully Verified
        |--------------------------------------------------------------------------
        */

        $client->login_otp = null;

        $client->login_otp_expires_at = null;

        $client->login_otp_attempts = 0;

        $client->login_otp_locked_until = null;

        $client->save();


        /*
        |--------------------------------------------------------------------------
        | Save Successful Verification
        |--------------------------------------------------------------------------
        */

        OtpHistory::create([
            'channel' => 'email',
            'recipient' => $client->email,
            'action' => 'verify',
            'status' => 'success',
            'ip_address' => $request->ip(),
            'message' => 'OTP verified successfully.',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Login Client
        |--------------------------------------------------------------------------
        */

        Auth::guard('client')->login($client);

        $request->session()->regenerate();

        $request->session()->forget('client_login_id');


        return redirect()
            ->route('client.dashboard')
            ->with(
                'success',
                'Login successful!'
            );
    }


    // ------------------ LOGOUT ------------------

    public function logout(Request $request)
    {
        Auth::guard('client')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()
            ->route('client.login.form');
    }


    // ------------------ DASHBOARD ------------------

    public function dashboard()
    {
        if (!Auth::guard('client')->check()) {

            return redirect()
                ->route('client.login.form');
        }

        return view('client.dashboard');
    }


    // ------------------ FORGOT PASSWORD FORM ------------------

    public function forgotPasswordForm()
    {
        return view('client.forgot-password');
    }


    // ------------------ SEND RESET OTP ------------------

    public function sendResetOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);


        $client = Client::where(
            'email',
            $request->email
        )->first();


        if (!$client) {

            return back()
                ->withErrors([
                    'email' => 'No account found with this email.',
                ]);
        }


        $otp = random_int(100000, 999999);

        $token = Str::random(60);


        PasswordReset::create([
            'email' => $client->email,
            'token' => $token,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(15),
        ]);


        Mail::raw(
            "Your password reset OTP is: {$otp}\nThis OTP will expire in 15 minutes.",
            function ($mail) use ($client) {
                $mail->to($client->email)
                    ->subject("Password Reset OTP");
            }
        );


        return redirect()
            ->route(
                'client.reset.verify-otp',
                [
                    'token' => $token,
                ]
            )
            ->with(
                'success',
                'Password reset OTP has been sent to your email.'
            );
    }


    // ------------------ VERIFY RESET OTP FORM ------------------

    public function verifyResetOtpForm(
        Request $request,
        $token
    ) {
        $reset = PasswordReset::where(
            'token',
            $token
        )->first();


        if (
            !$reset ||
            now()->gt($reset->expires_at)
        ) {

            return redirect()
                ->route('client.forgot-password')
                ->withErrors([
                    'token' => 'Invalid or expired reset token.',
                ]);
        }


        return view(
            'client.verify-reset-otp',
            [
                'token' => $token,
                'email' => $reset->email,
            ]
        );
    }


    // ------------------ VERIFY RESET OTP ------------------

    public function verifyResetOtp(
        Request $request,
        $token
    ) {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);


        $reset = PasswordReset::where(
            'token',
            $token
        )->first();


        if (
            !$reset ||
            now()->gt($reset->expires_at)
        ) {

            return redirect()
                ->route('client.forgot-password')
                ->withErrors([
                    'token' => 'Invalid or expired reset token.',
                ]);
        }


        if ((string) $reset->otp !== (string) $request->otp) {

            return back()
                ->withErrors([
                    'otp' => 'Invalid OTP',
                ]);
        }


        return redirect()
            ->route(
                'client.reset.password',
                [
                    'token' => $token,
                ]
            )
            ->with(
                'success',
                'OTP verified! Please enter your new password.'
            );
    }


    // ------------------ RESET PASSWORD FORM ------------------

    public function resetPasswordForm(
        Request $request,
        $token
    ) {
        $reset = PasswordReset::where(
            'token',
            $token
        )->first();


        if (!$reset) {

            return redirect()
                ->route('client.forgot-password')
                ->withErrors([
                    'token' => 'Invalid token.',
                ]);
        }


        return view(
            'client.reset-password',
            [
                'token' => $token,
            ]
        );
    }


    // ------------------ RESET PASSWORD ------------------

    public function resetPassword(
        Request $request,
        $token
    ) {
        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);


        $reset = PasswordReset::where(
            'token',
            $token
        )->first();


        if (!$reset) {

            return redirect()
                ->route('client.forgot-password')
                ->withErrors([
                    'token' => 'Invalid token.',
                ]);
        }


        $client = Client::where(
            'email',
            $reset->email
        )->first();


        if (!$client) {

            return redirect()
                ->route('client.forgot-password')
                ->withErrors([
                    'email' => 'Client not found.',
                ]);
        }


        $client->password = Hash::make(
            $request->password
        );

        $client->save();


        PasswordReset::where(
            'token',
            $token
        )->delete();


        return redirect()
            ->route('client.login.form')
            ->with(
                'success',
                'Password has been reset successfully. Please login.'
            );
    }
}