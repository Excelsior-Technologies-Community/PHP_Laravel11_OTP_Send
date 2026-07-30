@extends('client.layout')

@section('content')
<div class="auth-box">
    <div class="auth-title">Reset Password</div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('client.reset.password', ['token' => $token]) }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">New Password</label>
            <input name="password" type="password" class="form-control" placeholder="Enter new password" required minlength="6">
        </div>

        <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <input name="password_confirmation" type="password" class="form-control" placeholder="Confirm new password" required>
        </div>

        <button class="btn btn-custom w-100 mt-2">Reset Password</button>

        <div class="text-center mt-3">
            <a href="{{ route('client.login.form') }}">Back to Login</a>
        </div>
    </form>
</div>
@endsection