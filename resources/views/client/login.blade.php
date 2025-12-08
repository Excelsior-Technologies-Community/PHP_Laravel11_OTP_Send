@extends('client.layout')

@section('content')
<div class="auth-box">
    <!-- Login page title (OTP-based authentication) -->
    <div class="auth-title">Client Login</div>

    <!-- Success message display (from registration redirect) -->
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- EMAIL-ONLY LOGIN FORM (triggers OTP) -->
    <form method="POST" action="{{ route('client.login.sendOtp') }}">
        @csrf  <!-- CSRF protection token -->

        <!-- EMAIL INPUT (ONLY FIELD REQUIRED) -->
        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input name="email" type="email" class="form-control" placeholder="example@mail.com" required>
            <!-- Triggers OTP send to this email -->
        </div>

        <!-- SEND OTP BUTTON -->
        <button class="btn btn-custom w-100 mt-2">Send OTP</button>
        <!-- Submits email → Controller sends 6-digit OTP via email -->

        <!-- Registration link for new users -->
        <div class="text-center mt-3">
            Don't have an account? 
            <a href="{{ route('client.register.form') }}">Register Now</a>
            <!-- Links back to registration form -->
        </div>
    </form>
</div>
@endsection
