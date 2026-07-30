@component('mail::message')
# Your OTP Code

Your OTP is **{{ $otp }}**. It expires in 5 minutes.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
