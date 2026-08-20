@extends('client.layout')

@section('content')

<div class="auth-box">

    <div class="auth-title">
        Edit Profile
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
        action="{{ route('client.profile.update') }}">

        @csrf

        @method('PUT')


        <div class="mb-3">

            <label class="form-label">
                Full Name
            </label>

            <input
                name="name"
                class="form-control"
                value="{{ old('name', $client->name) }}"
                required>

        </div>


        <div class="mb-3">

            <label class="form-label">
                Email Address
            </label>

            <input
                type="email"
                class="form-control"
                value="{{ $client->email }}"
                disabled>

            <small class="text-muted">
                Email cannot be changed because it is used for OTP login.
            </small>

        </div>


        <div class="mb-3">

            <label class="form-label">
                Phone Number
            </label>

            <input
                name="phone"
                class="form-control"
                value="{{ old('phone', $client->phone) }}"
                placeholder="9876543210">

        </div>


        <button
            type="submit"
            class="btn btn-custom w-100">
            Update Profile
        </button>


        <div class="text-center mt-3">

            <a href="{{ route('client.dashboard') }}">
                Back to Dashboard
            </a>

        </div>

    </form>

</div>

@endsection