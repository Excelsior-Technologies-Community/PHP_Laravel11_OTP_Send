@extends('client.layout')

@section('content')
<div class="auth-box" style="max-width:600px;">
    <div class="auth-title">Dashboard</div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="mb-4">
        <p><strong>Name:</strong> {{ Auth::guard('client')->user()->name }}</p>
        <p><strong>Email:</strong> {{ Auth::guard('client')->user()->email }}</p>
        @if(Auth::guard('client')->user()->phone)
            <p><strong>Phone:</strong> {{ Auth::guard('client')->user()->phone }}</p>
        @endif
    </div>

    <form method="POST" action="{{ route('client.logout') }}">
        @csrf
        <button class="btn btn-custom w-100">Logout</button>
    </form>
</div>
@endsection