@extends('client.layout')

@section('content')

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">
                OTP Login History
            </h2>

            <p class="text-muted mb-0">
                Review your recent OTP login activity.
            </p>

        </div>


        <a
            href="{{ route('client.dashboard') }}"
            class="btn btn-outline-secondary">
            Dashboard
        </a>

    </div>


    {{-- STATISTICS --}}

    <div class="row g-3 mb-4">

        <div class="col-md-4">

            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <div class="text-muted">
                        Total Attempts
                    </div>

                    <h2 class="fw-bold">
                        {{ $totalAttempts }}
                    </h2>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <div class="text-muted">
                        Successful
                    </div>

                    <h2 class="fw-bold text-success">
                        {{ $successfulAttempts }}
                    </h2>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <div class="text-muted">
                        Failed
                    </div>

                    <h2 class="fw-bold text-danger">
                        {{ $failedAttempts }}
                    </h2>

                </div>

            </div>

        </div>

    </div>


    {{-- FILTERS --}}

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <form
                method="GET"
                action="{{ route('client.otp.history') }}">

                <div class="row g-3">

                    <div class="col-md-3">

                        <label class="form-label">
                            Search
                        </label>

                        <input
                            name="search"
                            class="form-control"
                            placeholder="Search..."
                            value="{{ request('search') }}">

                    </div>


                    <div class="col-md-2">

                        <label class="form-label">
                            Status
                        </label>

                        <select
                            name="status"
                            class="form-select">

                            <option value="">
                                All
                            </option>

                            <option
                                value="success"
                                @selected(request('status')==='success' )>
                                Success
                            </option>

                            <option
                                value="failed"
                                @selected(request('status')==='failed' )>
                                Failed
                            </option>

                            <option
                                value="expired"
                                @selected(request('status')==='expired' )>
                                Expired
                            </option>

                        </select>

                    </div>


                    <div class="col-md-2">

                        <label class="form-label">
                            Channel
                        </label>

                        <select
                            name="channel"
                            class="form-select">

                            <option value="">
                                All
                            </option>

                            <option
                                value="email"
                                @selected(request('channel')==='email' )>
                                Email
                            </option>

                            <option
                                value="sms"
                                @selected(request('channel')==='sms' )>
                                SMS
                            </option>

                        </select>

                    </div>


                    <div class="col-md-2">

                        <label class="form-label">
                            From
                        </label>

                        <input
                            type="date"
                            name="date_from"
                            class="form-control"
                            value="{{ request('date_from') }}">

                    </div>


                    <div class="col-md-2">

                        <label class="form-label">
                            To
                        </label>

                        <input
                            type="date"
                            name="date_to"
                            class="form-control"
                            value="{{ request('date_to') }}">

                    </div>


                    <div class="col-md-1 d-flex align-items-end">

                        <button
                            class="btn btn-primary w-100">
                            Filter
                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>


    {{-- TABLE --}}

    <div class="card border-0 shadow-sm">

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">

                        <tr>

                            <th>#</th>
                            <th>Channel</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>IP Address</th>
                            <th>Date</th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($histories as $history)

                        <tr>

                            <td>
                                {{ $histories->firstItem() + $loop->index }}
                            </td>


                            <td>

                                @if($history->channel === 'email')

                                <span class="badge bg-primary">
                                    Email
                                </span>

                                @else

                                <span class="badge bg-info text-dark">
                                    SMS
                                </span>

                                @endif

                            </td>


                            <td>
                                {{ $history->email ?? '-' }}
                            </td>


                            <td>
                                {{ $history->phone ?? '-' }}
                            </td>


                            <td>

                                @if($history->status === 'success')

                                <span class="badge bg-success">
                                    Success
                                </span>

                                @elseif($history->status === 'failed')

                                <span class="badge bg-danger">
                                    Failed
                                </span>

                                @else

                                <span class="badge bg-warning text-dark">
                                    Expired
                                </span>

                                @endif

                            </td>


                            <td>
                                {{ $history->ip_address ?? '-' }}
                            </td>


                            <td>
                                {{ $history->created_at->format('d M Y, h:i A') }}
                            </td>

                        </tr>

                        @empty

                        <tr>

                            <td
                                colspan="7"
                                class="text-center py-5 text-muted">
                                No OTP login history found.
                            </td>

                        </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        @if($histories->hasPages())

        <div class="card-footer bg-white">

            {{ $histories->links() }}

        </div>

        @endif

    </div>

</div>

@endsection