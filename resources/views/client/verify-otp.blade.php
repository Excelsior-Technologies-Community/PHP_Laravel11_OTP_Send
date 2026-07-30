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

    <p class="text-center text-muted mb-3">Enter the 6-digit code sent to your email</p>

    <form method="POST" action="{{ route('client.otp.verify') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Enter OTP</label>
            <input name="otp" class="form-control" placeholder="6-digit code" required autofocus maxlength="6">
        </div>

        <button class="btn btn-custom w-100 mt-2">Verify OTP</button>

        <div class="text-center mt-3">
            <a href="{{ route('client.login.form') }}">Back to Login</a>
        </div>
    </form>

    <div class="text-center mt-3">
        <div id="countdown" class="text-muted mb-2" style="font-size: 14px;">
            OTP Expires in: <span id="timer"></span>
        </div>
        <button type="button" class="btn btn-link p-0" id="resendBtn" onclick="resendOtp()" style="font-size:14px;">
            Resend OTP
        </button>
    </div>
</div>

<form id="resendForm" action="{{ route('client.login.resendOtp') }}" method="POST" style="display:none;">
    @csrf
</form>

<script>
    var countdownSeconds = {{ isset($expires_at) ? max(1, $expires_at->getTimestamp() - time()) : 300 }};
    var resendCooldown = 0;

    function updateTimer() {
        if (countdownSeconds <= 0) {
            document.getElementById('timer').textContent = 'Expired';
            document.getElementById('timer').style.color = 'red';
            document.getElementById('resendBtn').disabled = false;
            return;
        }
        var mins = Math.floor(countdownSeconds / 60);
        var secs = countdownSeconds % 60;
        document.getElementById('timer').textContent = mins + ':' + (secs < 10 ? '0' : '') + secs;
        if (secs <= 10 && mins === 0) {
            document.getElementById('timer').style.color = 'red';
        }
        countdownSeconds--;
    }

    function resendOtp() {
        if (resendCooldown > 0) return;
        document.getElementById('resendBtn').disabled = true;
        document.getElementById('resendBtn').textContent = 'Sending...';
        document.getElementById('resendForm').submit();
    }

    updateTimer();
    setInterval(updateTimer, 1000);

    setTimeout(function() {
        document.getElementById('resendBtn').disabled = true;
        document.getElementById('resendBtn').textContent = 'Wait 30s';
        resendCooldown = 30;
        var cooldownInterval = setInterval(function() {
            resendCooldown--;
            if (resendCooldown <= 0) {
                clearInterval(cooldownInterval);
                document.getElementById('resendBtn').disabled = false;
                document.getElementById('resendBtn').textContent = 'Resend OTP';
            } else {
                document.getElementById('resendBtn').textContent = 'Wait ' + resendCooldown + 's';
            }
        }, 1000);
    }, 30000);
</script>
@endsection