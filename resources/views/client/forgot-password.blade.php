@extends('client.layout')

@section('content')

<div class="auth-box">

    <div class="auth-title">
        Forgot Password
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


    <form
        method="POST"
        action="{{ route(
            'client.forgot-password.send'
        ) }}">

        @csrf


        <div class="mb-3">

            <label class="form-label">
                Email Address
            </label>

            <input
                name="email"
                type="email"
                class="form-control"
                placeholder="example@mail.com"
                value="{{ old('email') }}"
                required>

        </div>


        <button
            class="btn btn-custom w-100">
            Send Reset OTP
        </button>


        <div class="text-center mt-3">

            <a
                href="{{ route(
                    'client.login.form'
                ) }}">
                Back to Login
            </a>

        </div>

    </form>

</div>

@endsection