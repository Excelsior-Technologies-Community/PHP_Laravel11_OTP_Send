@extends('client.layout')

@section('content')

<div class="auth-box">

    <div class="auth-title">
        Verify Reset OTP
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


    <p class="text-center text-muted mb-3">

        Enter the 6-digit OTP sent to

        <strong>
            {{ $email }}
        </strong>

    </p>


    <form
        method="POST"
        action="{{ route(
            'client.reset.verify-otp.submit',
            ['token' => $token]
        ) }}">

        @csrf


        <div class="mb-3">

            <label class="form-label">
                Enter 6-digit OTP
            </label>

            <input
                name="otp"
                class="form-control text-center"
                maxlength="6"
                inputmode="numeric"
                pattern="[0-9]{6}"
                placeholder="123456"
                required
                autofocus>

        </div>


        <button
            class="btn btn-custom w-100">
            Verify OTP
        </button>


        <div class="text-center mt-3">

            <a
                href="{{ route(
                    'client.forgot-password.form'
                ) }}">
                Back to Forgot Password
            </a>

        </div>

    </form>

</div>

@endsection