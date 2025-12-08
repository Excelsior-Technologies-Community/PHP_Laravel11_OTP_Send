<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ClientAuthController extends Controller
{
    // ------------------ REGISTRATION FORM ------------------
    // Display client registration form
    public function registerForm() {
        return view('client.registration');
    }

    // ------------------ REGISTER NEW CLIENT ------------------
    // Handle client registration with email verification
    public function register(Request $request) {
        // Validate registration form inputs
        $request->validate([
            'name' => 'required',              // Client full name required
            'email'=> 'required|email|unique:clients',  // Unique email validation
            'password'=> 'required|min:6',     // Password minimum 6 characters
            'phone'=> 'nullable'               // Phone optional
        ]);

        // Create new client record with hashed password
        $client = Client::create([
            'name' => $request->name,
            'email'=> $request->email,
            'phone'=> $request->phone,
            'password'=> Hash::make($request->password),  // Password hashed for security
        ]);

        // -------- SEND WELCOME EMAIL ----------
        Mail::raw("Hello {$client->name}, your registration is successful!", function($msg) use ($client) {
            $msg->to($client->email)     // Send to client email
                ->subject("Welcome to Our App");
        });

        // Redirect to login with success message
        return redirect()->route('client.login.form')
                ->with('success','Registration successful! Please login.');
    }

    // ------------------ LOGIN FORM ------------------
    // Display client login form
    public function loginForm() {
        return view('client.login');
    }

    // ------------------ SEND OTP (EMAIL VERIFICATION) ------------------
    // Send 6-digit OTP to client's email for login verification
    public function sendOtp(Request $request) {
        // Validate email input
        $request->validate(['email' => 'required|email']);

        // Find client by email
        $client = Client::where('email', $request->email)->first();

        // Return error if client not found
        if (!$client) {
            return back()->withErrors(['email' => 'Client not found']);
        }

        // Generate random 6-digit OTP
        $otp = rand(100000, 999999);

        // Save OTP and expiration (5 minutes) to database
        $client->login_otp = $otp;
        $client->login_otp_expires_at = now()->addMinutes(5);
        $client->save();

        // -------- Send OTP via Email --------
        $subject = "Your Login OTP";
        $message = "Your OTP is: {$otp} \nThis OTP will expire in 5 minutes.";

        Mail::raw($message, function($mail) use ($client, $subject) {
            $mail->to($client->email)
                ->subject($subject);
        });

        // Store client ID in session for OTP verification
        session(['client_login_id' => $client->id]);

        return redirect()->route('client.otp.form')
                ->with('success','OTP has been sent to your email!');
    }

    // ------------------ OTP VERIFICATION FORM ------------------
    // Display OTP input form
    public function otpForm() {
        return view('client.verify-otp');
    }

    // ------------------ VERIFY OTP & LOGIN ------------------
    // Validate OTP and login client if correct
    public function verifyOtp(Request $request) {
        // Validate 6-digit OTP input
        $request->validate(['otp' => 'required|digits:6']);

        // Get client from session
        $client = Client::find(session('client_login_id'));

        // Session expired check
        if (!$client) {
            return redirect()->route('client.login.form')
                           ->withErrors(['otp' => 'Session expired']);
        }

        // Invalid OTP check
        if ($client->login_otp != $request->otp) {
            return back()->withErrors(['otp' => 'Invalid OTP']);
        }

        // OTP expired check
        if (now()->gt($client->login_otp_expires_at)) {
            return back()->withErrors(['otp' => 'OTP expired']);
        }

        // OTP VALID → Clear OTP and login client
        $client->login_otp = null;
        $client->login_otp_expires_at = null;
        $client->save();

        // Authenticate client session
        Auth::login($client);

        return redirect('/')->with('success','Login successful!');
    }

    // ------------------ LOGOUT ------------------
    // Logout client and redirect to login
    public function logout() {
        // End client authentication session
        Auth::logout();
        return redirect()->route('client.login.form');
    }
}
