@extends('client.layout')

@section('content')

<div class="auth-box">

    <div class="auth-title">
        Verify OTP
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


    <div class="text-center mb-3">

        @if($channel === 'sms')

        <p class="text-muted">
            Enter the 6-digit OTP sent to your phone.
        </p>

        @else

        <p class="text-muted">
            Enter the 6-digit OTP sent to your email.
        </p>

        @endif

    </div>


    <form method="POST"
        action="{{ route('client.otp.verify') }}">

        @csrf

        <div class="mb-3">

            <label class="form-label">
                Enter 6-digit OTP
            </label>

            <input
                name="otp"
                type="text"
                inputmode="numeric"
                pattern="[0-9]{6}"
                maxlength="6"
                class="form-control text-center"
                style="font-size:24px;letter-spacing:8px;"
                placeholder="••••••"
                required
                autofocus>

        </div>


        <button
            type="submit"
            class="btn btn-custom w-100">
            Verify OTP
        </button>

    </form>


    <div class="text-center mt-4">

        <div
            id="countdown"
            class="text-muted mb-2">
            OTP Expires in:
            <strong id="timer"></strong>
        </div>


        <form
            method="POST"
            action="{{ route('client.login.resendOtp') }}">

            @csrf

            <button
                type="submit"
                id="resendBtn"
                class="btn btn-link">
                Resend OTP
            </button>

        </form>

    </div>


    <div class="text-center mt-3">

        <a href="{{ route('client.login.form') }}">
            Back to Login
        </a>

    </div>

</div>


<script>
    let countdownSeconds = {
        {
            isset($expires_at) && $expires_at ?
                max(0, $expires_at - > getTimestamp() - time()) :
                0
        }
    };

    let timerElement =
        document.getElementById('timer');

    let resendButton =
        document.getElementById('resendBtn');


    function updateTimer() {
        if (countdownSeconds <= 0) {

            timerElement.textContent =
                'Expired';

            resendButton.disabled = false;

            return;
        }


        let minutes =
            Math.floor(countdownSeconds / 60);

        let seconds =
            countdownSeconds % 60;


        timerElement.textContent =
            minutes + ':' +
            String(seconds).padStart(2, '0');


        countdownSeconds--;
    }


    updateTimer();

    setInterval(updateTimer, 1000);
</script>

@endsection