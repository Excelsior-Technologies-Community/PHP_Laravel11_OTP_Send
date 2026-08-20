<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\OtpLoginHistory;
use App\Models\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;
use App\Notifications\OtpSmsNotification;

class ClientAuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | REGISTRATION
    |--------------------------------------------------------------------------
    */

    public function registerForm()
    {
        return view('client.registration');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:clients,email',
            'password' => 'required|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
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
                    ->subject('Welcome to Our App');
            }
        );

        Auth::guard('client')->login($client);

        return redirect()
            ->route('client.dashboard')
            ->with('success', 'Registration successful! Welcome.');
    }


    /*
    |--------------------------------------------------------------------------
    | LOGIN FORM
    |--------------------------------------------------------------------------
    */

    public function loginForm()
    {
        return view('client.login');
    }


    /*
    |--------------------------------------------------------------------------
    | SEND LOGIN OTP
    |--------------------------------------------------------------------------
    */

    public function sendOtp(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'channel' => 'required|in:email,sms',
        ]);

        $channel = $request->channel;
        $login = trim($request->login);

        /*
        |--------------------------------------------------------------------------
        | RATE LIMIT
        |--------------------------------------------------------------------------
        */

        $rateLimitKey = 'send-login-otp:' .
            Str::lower($login) . ':' .
            $request->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {

            $seconds = RateLimiter::availableIn($rateLimitKey);

            $minutes = ceil($seconds / 60);

            return back()->withErrors([
                'login' => "Too many OTP requests. Please try again in {$minutes} minute(s).",
            ])->withInput();
        }

        RateLimiter::hit($rateLimitKey, 600);


        /*
        |--------------------------------------------------------------------------
        | FIND CLIENT
        |--------------------------------------------------------------------------
        */

        if ($channel === 'email') {

            $client = Client::where('email', $login)->first();
        } else {

            $client = Client::where('phone', $login)->first();
        }


        if (!$client) {
            return back()
                ->withErrors([
                    'login' => 'No client account found with these details.',
                ])
                ->withInput();
        }


        /*
        |--------------------------------------------------------------------------
        | GENERATE OTP
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
        | STORE LOGIN SESSION
        |--------------------------------------------------------------------------
        */

        session([
            'client_login_id' => $client->id,
            'client_login_channel' => $channel,
        ]);


        /*
        |--------------------------------------------------------------------------
        | SEND EMAIL OTP
        |--------------------------------------------------------------------------
        */

        if ($channel === 'email') {

            $message = "Your Login OTP is: {$otp}\n\n"
                . "This OTP will expire in 5 minutes.\n\n"
                . "If you did not request this OTP, please ignore this email.";

            Mail::raw($message, function ($mail) use ($client) {
                $mail->to($client->email)
                    ->subject('Your Login OTP');
            });
        }


        /*
        |--------------------------------------------------------------------------
        | SEND SMS OTP
        |--------------------------------------------------------------------------
        */

        if ($channel === 'sms') {

            if (!$client->phone) {
                return back()->withErrors([
                    'login' => 'No phone number is registered for this account.',
                ]);
            }

            Notification::route('twilio', $client->phone)
                ->notify(new OtpSmsNotification($otp));
        }


        return redirect()
            ->route('client.otp.form')
            ->with('success', 'OTP has been sent successfully.');
    }


    /*
    |--------------------------------------------------------------------------
    | RESEND OTP
    |--------------------------------------------------------------------------
    */

    public function resendOtp(Request $request)
    {
        $client = Client::find(session('client_login_id'));

        if (!$client) {
            return redirect()
                ->route('client.login.form')
                ->withErrors([
                    'otp' => 'Session expired. Please login again.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | RESEND RATE LIMIT
        |--------------------------------------------------------------------------
        */

        $key = 'resend-login-otp:' .
            $client->id . ':' .
            $request->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {

            $seconds = RateLimiter::availableIn($key);

            $minutes = ceil($seconds / 60);

            return back()->withErrors([
                'otp' => "Too many resend requests. Please try again in {$minutes} minute(s).",
            ]);
        }

        RateLimiter::hit($key, 600);


        /*
        |--------------------------------------------------------------------------
        | EXISTING OTP CHECK
        |--------------------------------------------------------------------------
        */

        if (
            $client->login_otp &&
            $client->login_otp_expires_at &&
            now()->lt($client->login_otp_expires_at)
        ) {
            return back()->withErrors([
                'otp' => 'OTP is still valid. Please wait before requesting another OTP.',
            ]);
        }


        $otp = random_int(100000, 999999);

        $client->login_otp = $otp;
        $client->login_otp_expires_at = now()->addMinutes(5);
        $client->login_otp_attempts = 0;
        $client->login_otp_locked_until = null;
        $client->save();


        $channel = session('client_login_channel', 'email');


        if ($channel === 'sms') {

            Notification::route('twilio', $client->phone)
                ->notify(new OtpSmsNotification($otp));
        } else {

            Mail::raw(
                "Your new Login OTP is: {$otp}\n\nThis OTP will expire in 5 minutes.",
                function ($mail) use ($client) {
                    $mail->to($client->email)
                        ->subject('Your Login OTP - Resent');
                }
            );
        }


        return back()->with(
            'success',
            'New OTP has been sent successfully.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | OTP FORM
    |--------------------------------------------------------------------------
    */

    public function otpForm()
    {
        $client = Client::find(session('client_login_id'));

        if (!$client) {
            return redirect()
                ->route('client.login.form')
                ->withErrors([
                    'otp' => 'Session expired.',
                ]);
        }

        return view('client.verify-otp', [
            'expires_at' => $client->login_otp_expires_at,
            'client' => $client,
            'channel' => session('client_login_channel', 'email'),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | VERIFY LOGIN OTP
    |--------------------------------------------------------------------------
    */

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $client = Client::find(session('client_login_id'));

        if (!$client) {
            return redirect()
                ->route('client.login.form')
                ->withErrors([
                    'otp' => 'Session expired. Please login again.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK LOCK
        |--------------------------------------------------------------------------
        */

        if (
            $client->login_otp_locked_until &&
            now()->lt($client->login_otp_locked_until)
        ) {

            $remaining = now()->diffInSeconds(
                $client->login_otp_locked_until
            );

            $minutes = ceil($remaining / 60);

            $this->createOtpHistory(
                $client,
                'failed'
            );

            return back()->withErrors([
                'otp' => "Account temporarily locked. Try again in {$minutes} minute(s).",
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK OTP EXISTS
        |--------------------------------------------------------------------------
        */

        if (!$client->login_otp) {

            $this->createOtpHistory(
                $client,
                'expired'
            );

            return back()->withErrors([
                'otp' => 'No active OTP found. Please request a new OTP.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK EXPIRY
        |--------------------------------------------------------------------------
        */

        if (
            !$client->login_otp_expires_at ||
            now()->gt($client->login_otp_expires_at)
        ) {

            $this->createOtpHistory(
                $client,
                'expired'
            );

            return back()->withErrors([
                'otp' => 'OTP has expired. Please request a new OTP.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK OTP
        |--------------------------------------------------------------------------
        */

        if ((string) $client->login_otp !== (string) $request->otp) {

            $client->login_otp_attempts =
                (int) $client->login_otp_attempts + 1;

            if ($client->login_otp_attempts >= 3) {

                $client->login_otp_locked_until =
                    now()->addMinutes(15);

                $client->save();

                $this->createOtpHistory(
                    $client,
                    'failed'
                );

                return back()->withErrors([
                    'otp' =>
                    'Too many failed attempts. Account locked for 15 minutes.',
                ]);
            }

            $client->save();

            $this->createOtpHistory(
                $client,
                'failed'
            );

            return back()->withErrors([
                'otp' =>
                'Invalid OTP. Attempts: ' .
                    $client->login_otp_attempts .
                    '/3',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | SUCCESS
        |--------------------------------------------------------------------------
        */

        $this->createOtpHistory(
            $client,
            'success'
        );


        $client->login_otp = null;
        $client->login_otp_expires_at = null;
        $client->login_otp_attempts = 0;
        $client->login_otp_locked_until = null;
        $client->save();


        Auth::guard('client')->login($client);

        $request->session()->regenerate();

        session()->forget([
            'client_login_id',
            'client_login_channel',
        ]);


        return redirect()
            ->route('client.dashboard')
            ->with('success', 'Login successful!');
    }


    /*
    |--------------------------------------------------------------------------
    | OTP HISTORY HELPER
    |--------------------------------------------------------------------------
    */

    private function createOtpHistory(
        Client $client,
        string $status
    ): void {

        OtpLoginHistory::create([
            'client_id' => $client->id,
            'email' => $client->email,
            'phone' => $client->phone,
            'channel' => session(
                'client_login_channel',
                'email'
            ),
            'status' => $status,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    public function logout(Request $request)
    {
        Auth::guard('client')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('client.login.form')
            ->with('success', 'You have been logged out.');
    }


    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function dashboard()
    {
        if (!Auth::guard('client')->check()) {
            return redirect()
                ->route('client.login.form');
        }

        return view('client.dashboard');
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT PROFILE FORM
    |--------------------------------------------------------------------------
    */

    public function editProfile()
    {
        $client = Auth::guard('client')->user();

        return view('client.profile-edit', [
            'client' => $client,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE PROFILE
    |--------------------------------------------------------------------------
    */

    public function updateProfile(Request $request)
    {
        $client = Auth::guard('client')->user();

        $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
        ]);

        $client->name = $request->name;
        $client->phone = $request->phone;
        $client->save();

        return redirect()
            ->route('client.profile.edit')
            ->with('success', 'Profile updated successfully.');
    }


    /*
    |--------------------------------------------------------------------------
    | CHANGE PASSWORD FORM
    |--------------------------------------------------------------------------
    */

    public function changePasswordForm()
    {
        return view('client.change-password');
    }


    /*
    |--------------------------------------------------------------------------
    | CHANGE PASSWORD
    |--------------------------------------------------------------------------
    */

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $client = Auth::guard('client')->user();

        if (
            !Hash::check(
                $request->current_password,
                $client->password
            )
        ) {
            return back()->withErrors([
                'current_password' =>
                'Current password is incorrect.',
            ]);
        }

        if (
            Hash::check(
                $request->password,
                $client->password
            )
        ) {
            return back()->withErrors([
                'password' =>
                'New password must be different from the current password.',
            ]);
        }

        $client->password = Hash::make(
            $request->password
        );

        $client->save();

        return redirect()
            ->route('client.dashboard')
            ->with(
                'success',
                'Password changed successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | OTP LOGIN HISTORY
    |--------------------------------------------------------------------------
    */

    public function otpHistory(Request $request)
    {
        $client = Auth::guard('client')->user();

        $query = OtpLoginHistory::where(
            'client_id',
            $client->id
        );


        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('channel', 'like', "%{$search}%");
            });
        }


        /*
        |--------------------------------------------------------------------------
        | STATUS FILTER
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled('status') &&
            in_array(
                $request->status,
                ['success', 'failed', 'expired']
            )
        ) {
            $query->where(
                'status',
                $request->status
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CHANNEL FILTER
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled('channel') &&
            in_array(
                $request->channel,
                ['email', 'sms']
            )
        ) {
            $query->where(
                'channel',
                $request->channel
            );
        }


        /*
        |--------------------------------------------------------------------------
        | DATE FILTER
        |--------------------------------------------------------------------------
        */

        if ($request->filled('date_from')) {
            $query->whereDate(
                'created_at',
                '>=',
                $request->date_from
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'created_at',
                '<=',
                $request->date_to
            );
        }


        $histories = $query
            ->latest()
            ->paginate(5)
            ->withQueryString();


        $totalAttempts = OtpLoginHistory::where(
            'client_id',
            $client->id
        )->count();

        $successfulAttempts = OtpLoginHistory::where(
            'client_id',
            $client->id
        )
            ->where('status', 'success')
            ->count();

        $failedAttempts = OtpLoginHistory::where(
            'client_id',
            $client->id
        )
            ->where('status', 'failed')
            ->count();


        return view('client.otp-history', [
            'histories' => $histories,
            'totalAttempts' => $totalAttempts,
            'successfulAttempts' => $successfulAttempts,
            'failedAttempts' => $failedAttempts,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | FORGOT PASSWORD FORM
    |--------------------------------------------------------------------------
    */

    public function forgotPasswordForm()
    {
        return view('client.forgot-password');
    }


    /*
    |--------------------------------------------------------------------------
    | SEND RESET OTP
    |--------------------------------------------------------------------------
    */

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
            return back()->withErrors([
                'email' =>
                'No account found with this email.',
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
            "Your password reset OTP is: {$otp}\n\n"
                . "This OTP will expire in 15 minutes.",
            function ($mail) use ($client) {
                $mail->to($client->email)
                    ->subject('Password Reset OTP');
            }
        );

        return redirect()
            ->route(
                'client.reset.verify-otp',
                ['token' => $token]
            )
            ->with(
                'success',
                'Password reset OTP has been sent to your email.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | VERIFY RESET OTP FORM
    |--------------------------------------------------------------------------
    */

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
                ->route('client.forgot-password.form')
                ->withErrors([
                    'token' =>
                    'Invalid or expired reset token.',
                ]);
        }

        return view('client.verify-reset-otp', [
            'token' => $token,
            'email' => $reset->email,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | VERIFY RESET OTP
    |--------------------------------------------------------------------------
    */

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
                ->route('client.forgot-password.form')
                ->withErrors([
                    'token' =>
                    'Invalid or expired reset token.',
                ]);
        }

        if ((string) $reset->otp !== (string) $request->otp) {

            return back()->withErrors([
                'otp' => 'Invalid OTP.',
            ]);
        }

        session([
            'password_reset_verified_' . $token => true,
        ]);

        return redirect()
            ->route(
                'client.reset.password.form',
                ['token' => $token]
            )
            ->with(
                'success',
                'OTP verified! Please enter your new password.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | RESET PASSWORD FORM
    |--------------------------------------------------------------------------
    */

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
                ->route('client.forgot-password.form')
                ->withErrors([
                    'token' => 'Invalid token.',
                ]);
        }

        if (
            !session(
                'password_reset_verified_' . $token
            )
        ) {
            return redirect()
                ->route(
                    'client.reset.verify-otp',
                    ['token' => $token]
                )
                ->withErrors([
                    'otp' =>
                    'Please verify the OTP first.',
                ]);
        }

        return view('client.reset-password', [
            'token' => $token,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | RESET PASSWORD
    |--------------------------------------------------------------------------
    */

    public function resetPassword(
        Request $request,
        $token
    ) {

        $request->validate([
            'password' =>
            'required|min:6|confirmed',
        ]);

        if (
            !session(
                'password_reset_verified_' . $token
            )
        ) {
            return redirect()
                ->route(
                    'client.reset.verify-otp',
                    ['token' => $token]
                )
                ->withErrors([
                    'otp' =>
                    'Please verify the OTP first.',
                ]);
        }

        $reset = PasswordReset::where(
            'token',
            $token
        )->first();

        if (!$reset) {
            return redirect()
                ->route('client.forgot-password.form')
                ->withErrors([
                    'token' => 'Invalid token.',
                ]);
        }

        if (now()->gt($reset->expires_at)) {
            return redirect()
                ->route('client.forgot-password.form')
                ->withErrors([
                    'token' =>
                    'Password reset token has expired.',
                ]);
        }

        $client = Client::where(
            'email',
            $reset->email
        )->first();

        if (!$client) {
            return redirect()
                ->route('client.forgot-password.form')
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

        session()->forget(
            'password_reset_verified_' . $token
        );

        return redirect()
            ->route('client.login.form')
            ->with(
                'success',
                'Password has been reset successfully. Please login.'
            );
    }
}
