@extends('client.layout')

@section('content')

<div class="auth-box" style="max-width:700px;">

    <div class="auth-title">
        Client Dashboard
    </div>


    @if(session('success'))

    <div class="alert alert-success">
        {{ session('success') }}
    </div>

    @endif


    <div class="card border-0 bg-light mb-4">

        <div class="card-body">

            <h5 class="fw-bold mb-3">
                Profile Information
            </h5>


            <p>
                <strong>Name:</strong>
                {{ Auth::guard('client')->user()->name }}
            </p>


            <p>
                <strong>Email:</strong>
                {{ Auth::guard('client')->user()->email }}
            </p>


            <p class="mb-0">

                <strong>Phone:</strong>

                {{ Auth::guard('client')->user()->phone ?? 'Not added' }}

            </p>

        </div>

    </div>


    <div class="d-grid gap-2">

        <a
            href="{{ route('client.profile.edit') }}"
            class="btn btn-outline-primary">
            👤 Edit Profile
        </a>


        <a
            href="{{ route('client.password.change.form') }}"
            class="btn btn-outline-warning">
            🔑 Change Password
        </a>


        <a
            href="{{ route('client.otp.history') }}"
            class="btn btn-outline-info">
            📊 OTP Login History
        </a>


        <form
            method="POST"
            action="{{ route('client.logout') }}">

            @csrf

            <button
                type="submit"
                class="btn btn-custom w-100">
                Logout
            </button>

        </form>

    </div>

</div>

@endsection