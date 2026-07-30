<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ClientAuthController extends Controller
{
    // ------------------ REGISTRATION FORM ------------------
    public function registerForm() {
        return view('client.registration');
    }

    // ------------------ REGISTER NEW CLIENT ------------------
    public function register(Request $request) {
        $request->validate([
            'name' => 'required',
            'email'=> 'required|email|unique:clients',
            'password'=> 'required|min:6|confirmed',
            'phone'=> 'nullable',
        ]);

        $client = Client::create([
            'name' => $request->name,
            'email'=> $request->email,
            'phone'=> $request->phone,
            'password'=> Hash::make($request->password),
        ]);

        Mail::raw("Hello {$client->name}, your registration is successful!", function($msg) use ($client) {
            $msg->to($client->email)
                ->subject("Welcome to Our App");
        });

        Auth::guard('client')->login($client);

        return redirect()->route('client.dashboard')
                ->with('success','Registration successful! Welcome.');
    }

    // ------------------ LOGIN FORM ------------------
    public function loginForm() {
        return view('client.login');
    }

    // ------------------ SEND OTP (EMAIL VERIFICATION) ------------------
    public function sendOtp(Request $request) {
        $request->validate(['email' => 'required|email']);

        $client = Client::where('email', $request->email)->first();

        if (!$client) {
            return back()->withErrors(['email' => 'Client not found']);
        }

        $otp = rand(100000, 999999);

        $client->login_otp = $otp;
        $client->login_otp_expires_at = now()->addMinutes(5);
        $client->login_otp_attempts = 0;
        $client->login_otp_locked_until = null;
        $client->save();

        $subject = "Your Login OTP";
        $message = "Your OTP is: {$otp} \nThis OTP will expire in 5 minutes.";

        Mail::raw($message, function($mail) use ($client, $subject) {
            $mail->to($client->email)
                ->subject($subject);
        });

        session(['client_login_id' => $client->id]);

        return redirect()->route('client.otp.form')
                ->with('success','OTP has been sent to your email!');
    }

    // ------------------ RESEND OTP ------------------
    public function resendOtp(Request $request) {
        $client = Client::find(session('client_login_id'));

        if (!$client) {
            return redirect()->route('client.login.form')
                           ->withErrors(['otp' => 'Session expired']);
        }

        if ($client->login_otp && now()->lt($client->login_otp_expires_at)) {
            return back()->withErrors(['otp' => 'OTP is still valid. Please wait before resending.']);
        }

        $otp = rand(100000, 999999);

        $client->login_otp = $otp;
        $client->login_otp_expires_at = now()->addMinutes(5);
        $client->login_otp_attempts = 0;
        $client->login_otp_locked_until = null;
        $client->save();

        $subject = "Your Login OTP (Resent)";
        $message = "Your new OTP is: {$otp} \nThis OTP will expire in 5 minutes.";

        Mail::raw($message, function($mail) use ($client, $subject) {
            $mail->to($client->email)
                ->subject($subject);
        });

        return back()->with('success','New OTP has been sent to your email!');
    }

    // ------------------ OTP VERIFICATION FORM ------------------
    public function otpForm() {
        $client = Client::find(session('client_login_id'));

        if (!$client) {
            return redirect()->route('client.login.form')
                           ->withErrors(['otp' => 'Session expired']);
        }

        return view('client.verify-otp', [
            'expires_at' => $client->login_otp_expires_at,
            'client' => $client,
        ]);
    }

    // ------------------ VERIFY OTP & LOGIN ------------------
    public function verifyOtp(Request $request) {
        $request->validate(['otp' => 'required|digits:6']);

        $client = Client::find(session('client_login_id'));

        if (!$client) {
            return redirect()->route('client.login.form')
                           ->withErrors(['otp' => 'Session expired']);
        }

        if ($client->login_otp_locked_until && now()->lt($client->login_otp_locked_until)) {
            $remaining = now()->diffInSeconds($client->login_otp_locked_until);
            $minutes = ceil($remaining / 60);
            return back()->withErrors(['otp' => "Account temporarily locked. Try again in {$minutes} minute(s)."]);
        }

        if ($client->login_otp != $request->otp) {
            $client->login_otp_attempts += 1;

            if ($client->login_otp_attempts >= 3) {
                $client->login_otp_locked_until = now()->addMinutes(15);
                $client->save();
                return back()->withErrors(['otp' => 'Too many failed attempts. Account locked for 15 minutes.']);
            }

            $client->save();
            return back()->withErrors(['otp' => 'Invalid OTP. Attempts: ' . $client->login_otp_attempts . '/3']);
        }

        if (now()->gt($client->login_otp_expires_at)) {
            return back()->withErrors(['otp' => 'OTP expired']);
        }

        $client->login_otp = null;
        $client->login_otp_expires_at = null;
        $client->login_otp_attempts = 0;
        $client->login_otp_locked_until = null;
        $client->save();

        Auth::guard('client')->login($client);

        return redirect()->route('client.dashboard');
    }

    // ------------------ LOGOUT ------------------
    public function logout() {
        Auth::guard('client')->logout();
        return redirect()->route('client.login.form');
    }

    // ------------------ DASHBOARD ------------------
    public function dashboard() {
        if (!Auth::guard('client')->check()) {
            return redirect()->route('client.login.form');
        }
        return view('client.dashboard');
    }

    // ------------------ FORGOT PASSWORD FORM ------------------
    public function forgotPasswordForm() {
        return view('client.forgot-password');
    }

    // ------------------ SEND RESET OTP ------------------
    public function sendResetOtp(Request $request) {
        $request->validate(['email' => 'required|email']);

        $client = Client::where('email', $request->email)->first();

        if (!$client) {
            return back()->withErrors(['email' => 'No account found with this email.']);
        }

        $otp = rand(100000, 999999);
        $token = Str::random(60);

        PasswordReset::create([
            'email' => $client->email,
            'token' => $token,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(15),
        ]);

        Mail::raw("Your password reset OTP is: {$otp}\nThis OTP will expire in 15 minutes.", function($mail) use ($client) {
            $mail->to($client->email)
                ->subject("Password Reset OTP");
        });

        return redirect()->route('client.reset.verify-otp', ['token' => $token])
                ->with('success','Password reset OTP has been sent to your email.');
    }

    // ------------------ VERIFY RESET OTP FORM ------------------
    public function verifyResetOtpForm(Request $request, $token) {
        $reset = PasswordReset::where('token', $token)->first();

        if (!$reset || now()->gt($reset->expires_at)) {
            return redirect()->route('client.forgot-password')
                           ->withErrors(['token' => 'Invalid or expired reset token.']);
        }

        return view('client.verify-reset-otp', [
            'token' => $token,
            'email' => $reset->email,
        ]);
    }

    // ------------------ VERIFY RESET OTP ------------------
    public function verifyResetOtp(Request $request, $token) {
        $request->validate(['otp' => 'required|digits:6']);

        $reset = PasswordReset::where('token', $token)->first();

        if (!$reset || now()->gt($reset->expires_at)) {
            return redirect()->route('client.forgot-password')
                           ->withErrors(['token' => 'Invalid or expired reset token.']);
        }

        if ($reset->otp !== $request->otp) {
            return back()->withErrors(['otp' => 'Invalid OTP']);
        }

        return redirect()->route('client.reset.password', ['token' => $token])
                ->with('success','OTP verified! Please enter your new password.');
    }

    // ------------------ RESET PASSWORD FORM ------------------
    public function resetPasswordForm(Request $request, $token) {
        $reset = PasswordReset::where('token', $token)->first();

        if (!$reset) {
            return redirect()->route('client.forgot-password')
                           ->withErrors(['token' => 'Invalid token.']);
        }

        return view('client.reset-password', ['token' => $token]);
    }

    // ------------------ RESET PASSWORD ------------------
    public function resetPassword(Request $request, $token) {
        $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $reset = PasswordReset::where('token', $token)->first();

        if (!$reset) {
            return redirect()->route('client.forgot-password')
                           ->withErrors(['token' => 'Invalid token.']);
        }

        $client = Client::where('email', $reset->email)->first();

        if (!$client) {
            return redirect()->route('client.forgot-password')
                           ->withErrors(['email' => 'Client not found.']);
        }

        $client->password = Hash::make($request->password);
        $client->save();

        PasswordReset::where('token', $token)->delete();

        return redirect()->route('client.login.form')
                ->with('success','Password has been reset successfully. Please login.');
    }
}