<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;


class OtpSmsNotification extends Notification
{
    protected $otp;
    public function __construct($otp){ $this->otp = $otp; }

    public function via($notifiable){
        return [TwilioChannel::class];
    }

    public function toTwilio($notifiable){
        return (new TwilioSmsMessage())
            ->content("Your OTP is {$this->otp}. Expires in 5 minutes.");
    }
}
