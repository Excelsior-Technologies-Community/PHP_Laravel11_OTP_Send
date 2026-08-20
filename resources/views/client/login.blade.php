@extends('client.layout')

@section('content')

<div class="auth-box">

    <div class="auth-title">
        Client Login
    </div>

    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
    </div>
    @endif

    <form method="POST"
        action="{{ route('client.login.sendOtp') }}">

        @csrf

        <div class="mb-3">

            <label class="form-label">
                Email / Phone
            </label>

            <input
                name="login"
                class="form-control"
                placeholder="Email or phone number"
                value="{{ old('login') }}"
                required>

        </div>


        <div class="mb-3">

            <label class="form-label">
                OTP Delivery Method
            </label>

            <select
                name="channel"
                id="channel"
                class="form-select"
                required>

                <option value="email">
                    Email OTP
                </option>

                <option value="sms">
                    SMS OTP
                </option>

            </select>

        </div>


        <div class="alert alert-info small">

            <strong>Email OTP:</strong>
            Enter your registered email.

            <br>

            <strong>SMS OTP:</strong>
            Enter your registered phone number.

        </div>


        <button
            type="submit"
            class="btn btn-custom w-100 mt-2">
            Send OTP
        </button>


        <div class="text-center mt-3">

            Don't have an account?

            <a href="{{ route('client.register.form') }}">
                Register Now
            </a>

        </div>


        <div class="text-center mt-2">

            <a href="{{ route('client.forgot-password.form') }}">
                Forgot Password?
            </a>

        </div>

    </form>

</div>

@endsection