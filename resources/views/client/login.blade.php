@extends('client.layout')

@section('content')
<div class="auth-box">
    <!-- Login page title (OTP-based authentication) -->
    <div class="auth-title">Client Login</div>

    <!-- Success message display (from registration redirect) -->
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <!-- EMAIL-ONLY LOGIN FORM (triggers OTP) -->
    <form method="POST" action="{{ route('client.login.sendOtp') }}">
        @csrf  <!-- CSRF protection token -->

        <!-- EMAIL INPUT (ONLY FIELD REQUIRED) -->
        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input name="email" type="email" class="form-control" placeholder="example@mail.com" required>
        </div>

        <!-- SEND OTP BUTTON -->
        <button class="btn btn-custom w-100 mt-2">Send OTP</button>

        <!-- Registration link for new users -->
        <div class="text-center mt-3">
            Don't have an account? 
            <a href="{{ route('client.register.form') }}">Register Now</a>
        </div>
        <div class="text-center mt-2">
            <a href="{{ route('client.forgot-password.form') }}">Forgot Password?</a>
        </div>
    </form>
</div>
@endsection
