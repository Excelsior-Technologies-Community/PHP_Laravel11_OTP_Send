<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>OTP History</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fb;
        }

        .page-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.06);
        }

        .stat-number {
            font-size: 28px;
            font-weight: 700;
        }

        .table-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.06);
        }

        .badge-success-custom {
            background: #198754;
        }

        .badge-failed-custom {
            background: #dc3545;
        }

        .badge-blocked-custom {
            background: #fd7e14;
        }

    </style>
</head>

<body>

<div class="page-container">

    <!-- HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="fw-bold mb-1">
                OTP Verification History
            </h1>

            <p class="text-muted mb-0">
                Monitor OTP requests and verification attempts.
            </p>

        </div>

    </div>


    <!-- STATISTICS -->

    <div class="row g-3 mb-4">

        <div class="col-md-3">

            <div class="stat-card">

                <div class="text-muted">
                    Total Activities
                </div>

                <div class="stat-number">
                    {{ $total }}
                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="stat-card">

                <div class="text-muted">
                    Successful
                </div>

                <div class="stat-number text-success">
                    {{ $successful }}
                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="stat-card">

                <div class="text-muted">
                    Failed
                </div>

                <div class="stat-number text-danger">
                    {{ $failed }}
                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="stat-card">

                <div class="text-muted">
                    Blocked
                </div>

                <div class="stat-number text-warning">
                    {{ $blocked }}
                </div>

            </div>

        </div>

    </div>


    <!-- FILTERS -->

    <div class="table-card mb-4">

        <form
            method="GET"
            action="{{ route('admin.otp-history.index') }}"
        >

            <div class="row g-3">

                <!-- SEARCH -->

                <div class="col-md-4">

                    <label class="form-label">
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        class="form-control"
                        placeholder="Recipient, IP address..."
                    >

                </div>


                <!-- CHANNEL -->

                <div class="col-md-2">

                    <label class="form-label">
                        Channel
                    </label>

                    <select
                        name="channel"
                        class="form-select"
                    >

                        <option value="">
                            All
                        </option>

                        <option
                            value="email"
                            @selected(request('channel') === 'email')
                        >
                            Email
                        </option>

                        <option
                            value="sms"
                            @selected(request('channel') === 'sms')
                        >
                            SMS
                        </option>

                    </select>

                </div>


                <!-- ACTION -->

                <div class="col-md-2">

                    <label class="form-label">
                        Action
                    </label>

                    <select
                        name="action"
                        class="form-select"
                    >

                        <option value="">
                            All
                        </option>

                        <option
                            value="send"
                            @selected(request('action') === 'send')
                        >
                            Send
                        </option>

                        <option
                            value="verify"
                            @selected(request('action') === 'verify')
                        >
                            Verify
                        </option>

                    </select>

                </div>


                <!-- STATUS -->

                <div class="col-md-2">

                    <label class="form-label">
                        Status
                    </label>

                    <select
                        name="status"
                        class="form-select"
                    >

                        <option value="">
                            All
                        </option>

                        <option
                            value="success"
                            @selected(request('status') === 'success')
                        >
                            Success
                        </option>

                        <option
                            value="failed"
                            @selected(request('status') === 'failed')
                        >
                            Failed
                        </option>

                        <option
                            value="blocked"
                            @selected(request('status') === 'blocked')
                        >
                            Blocked
                        </option>

                    </select>

                </div>


                <!-- BUTTONS -->

                <div class="col-md-2 d-flex align-items-end">

                    <div class="w-100">

                        <button class="btn btn-primary w-100">
                            Filter
                        </button>

                    </div>

                </div>

            </div>

        </form>

    </div>


    <!-- HISTORY TABLE -->

    <div class="table-card">

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Channel</th>

                        <th>Recipient</th>

                        <th>Action</th>

                        <th>Status</th>

                        <th>IP Address</th>

                        <th>Message</th>

                        <th>Date</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($histories as $history)

                        <tr>

                            <td>
                                #{{ $history->id }}
                            </td>


                            <td>

                                @if($history->channel === 'email')

                                    <span class="badge bg-primary">
                                        Email
                                    </span>

                                @else

                                    <span class="badge bg-info">
                                        SMS
                                    </span>

                                @endif

                            </td>


                            <td>
                                {{ $history->recipient }}
                            </td>


                            <td>

                                @if($history->action === 'send')

                                    <span class="badge bg-secondary">
                                        OTP Sent
                                    </span>

                                @else

                                    <span class="badge bg-dark">
                                        OTP Verification
                                    </span>

                                @endif

                            </td>


                            <td>

                                @if($history->status === 'success')

                                    <span class="badge badge-success-custom">
                                        Success
                                    </span>

                                @elseif($history->status === 'failed')

                                    <span class="badge badge-failed-custom">
                                        Failed
                                    </span>

                                @else

                                    <span class="badge badge-blocked-custom">
                                        Blocked
                                    </span>

                                @endif

                            </td>


                            <td>
                                {{ $history->ip_address ?? 'N/A' }}
                            </td>


                            <td>
                                {{ $history->message ?? '-' }}
                            </td>


                            <td>
                                {{ $history->created_at->format('d M Y, h:i A') }}
                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="8"
                                class="text-center text-muted py-4"
                            >
                                No OTP history found.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <!-- PAGINATION -->

        <div class="mt-3">

            {{ $histories->links('pagination::bootstrap-5') }}

        </div>

    </div>

</div>

</body>

</html>