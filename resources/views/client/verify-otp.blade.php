@extends('client.layout')

@section('content')

<div class="auth-box">

    <div class="auth-title">Verify OTP</div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('client.otp.verify') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Enter OTP</label>
            <input name="otp" class="form-control" placeholder="6-digit code" required>
        </div>

        <button class="btn btn-custom w-100 mt-2">Verify OTP</button>

        <div class="text-center mt-3">
            <a href="{{ route('client.login.form') }}">Back to Login</a>
        </div>

    </form>

</div>

@endsection
