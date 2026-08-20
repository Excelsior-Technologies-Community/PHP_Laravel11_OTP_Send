@extends('client.layout')

@section('content')

<div class="auth-box">

    <div class="auth-title">
        Change Password
    </div>


    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif


    @if($errors->any())
    <div class="alert alert-danger">

        <ul class="mb-0">

            @foreach($errors->all() as $error)

            <li>{{ $error }}</li>

            @endforeach

        </ul>

    </div>
    @endif


    <form
        method="POST"
        action="{{ route('client.password.change') }}">

        @csrf


        <div class="mb-3">

            <label class="form-label">
                Current Password
            </label>

            <input
                name="current_password"
                type="password"
                class="form-control"
                placeholder="Enter current password"
                required>

        </div>


        <div class="mb-3">

            <label class="form-label">
                New Password
            </label>

            <input
                name="password"
                type="password"
                class="form-control"
                placeholder="Enter new password"
                minlength="8"
                required>

        </div>


        <div class="mb-3">

            <label class="form-label">
                Confirm New Password
            </label>

            <input
                name="password_confirmation"
                type="password"
                class="form-control"
                placeholder="Confirm new password"
                minlength="8"
                required>

        </div>


        <button
            type="submit"
            class="btn btn-custom w-100">
            Change Password
        </button>


        <div class="text-center mt-3">

            <a href="{{ route('client.dashboard') }}">
                Back to Dashboard
            </a>

        </div>

    </form>

</div>

@endsection