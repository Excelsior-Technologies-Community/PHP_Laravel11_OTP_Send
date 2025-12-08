@extends('client.layout')

@section('content')
<div class="auth-box">
    <!-- Registration page title -->
    <div class="auth-title">Client Registration</div>

    <!-- Success message display (from registration completion) -->
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Client registration form -->
    <form method="POST" action="{{ route('client.register') }}">
        @csrf  <!-- Laravel CSRF protection token -->

        <!-- FULL NAME INPUT -->
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input name="name" class="form-control" placeholder="Enter your name" required>
            <!-- Required field - matches controller validation -->
        </div>

        <!-- EMAIL ADDRESS INPUT -->
        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input name="email" type="email" class="form-control" placeholder="example@mail.com" required>
            <!-- Email validation + unique check in controller -->
        </div>

        <!-- PHONE NUMBER INPUT (OPTIONAL) -->
        <div class="mb-3">
            <label class="form-label">Phone Number</label>
            <input name="phone" class="form-control" placeholder="9876543210">
            <!-- Optional field - no 'required' attribute -->
        </div>

        <!-- PASSWORD INPUT -->
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input name="password" type="password" class="form-control" placeholder="Enter password" required>
            <!-- Minimum 6 characters (controller validation) -->
        </div>

        <!-- REGISTER SUBMIT BUTTON -->
        <button class="btn btn-custom w-100 mt-2">Register</button>

        <!-- Login link for existing users -->
        <div class="text-center mt-3">
            Already have an account? 
            <a href="{{ route('client.login.form') }}">Login</a>
            <!-- Links to OTP-based login flow -->
        </div>
    </form>
</div>
@endsection
