<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\OtpHistory;
use App\Models\OtpRequestLimit;
use App\Models\OtpVerification;
use App\Notifications\OtpSmsNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class OtpController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | OTP Rate Limit Settings
    |--------------------------------------------------------------------------
    */

    private const MAX_OTP_REQUESTS = 3;

    private const RATE_LIMIT_MINUTES = 10;


    /*
    |--------------------------------------------------------------------------
    | SEND OTP
    |--------------------------------------------------------------------------
    */

    public function sendOtp(Request $req)
    {
        $req->validate([
            'to' => 'required',
            'channel' => 'required|in:email,sms',
        ]);

        $to = $req->to;
        $channel = $req->channel;
        $ipAddress = $req->ip();

        /*
        |--------------------------------------------------------------------------
        | CHECK OTP RATE LIMIT
        |--------------------------------------------------------------------------
        */

        $rateLimit = OtpRequestLimit::where('channel', $channel)
            ->where('recipient', $to)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | FIRST OTP REQUEST
        |--------------------------------------------------------------------------
        */

        if (!$rateLimit) {

            $rateLimit = OtpRequestLimit::create([
                'channel' => $channel,
                'recipient' => $to,
                'ip_address' => $ipAddress,
                'request_count' => 1,
                'window_started_at' => now(),
            ]);

        } else {

            /*
            |--------------------------------------------------------------------------
            | Calculate Window End
            |--------------------------------------------------------------------------
            */

            $windowEndsAt = $rateLimit->window_started_at
                ->copy()
                ->addMinutes(self::RATE_LIMIT_MINUTES);

            /*
            |--------------------------------------------------------------------------
            | 10 MINUTE WINDOW EXPIRED
            |--------------------------------------------------------------------------
            */

            if (now()->greaterThanOrEqualTo($windowEndsAt)) {

                $rateLimit->update([
                    'request_count' => 1,
                    'ip_address' => $ipAddress,
                    'window_started_at' => now(),
                ]);

                $rateLimit->refresh();

            } else {

                /*
                |--------------------------------------------------------------------------
                | BLOCK AFTER 3 REQUESTS
                |--------------------------------------------------------------------------
                */

                if ($rateLimit->request_count >= self::MAX_OTP_REQUESTS) {

                    /*
                    |------------------------------------------------------------------
                    | Calculate remaining time correctly
                    |------------------------------------------------------------------
                    */

                    $remainingSeconds = max(
                        0,
                        $windowEndsAt->timestamp - now()->timestamp
                    );

                    $remainingMinutes = max(
                        1,
                        (int) ceil($remainingSeconds / 60)
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Save blocked request in history
                    |--------------------------------------------------------------------------
                    */

                    OtpHistory::create([
                        'channel' => $channel,
                        'recipient' => $to,
                        'action' => 'send',
                        'status' => 'blocked',
                        'ip_address' => $ipAddress,
                        'message' => "OTP request limit exceeded. Try again in {$remainingMinutes} minute(s).",
                    ]);

                    return response()->json([
                        'status' => false,
                        'message' => "Too many OTP requests. Please try again in {$remainingMinutes} minute(s).",
                        'retry_after_minutes' => $remainingMinutes,
                    ], 429);
                }

                /*
                |--------------------------------------------------------------------------
                | INCREASE REQUEST COUNT
                |--------------------------------------------------------------------------
                */

                $rateLimit->increment('request_count');

                $rateLimit->update([
                    'ip_address' => $ipAddress,
                ]);

                $rateLimit->refresh();
            }
        }


        /*
        |--------------------------------------------------------------------------
        | GENERATE OTP
        |--------------------------------------------------------------------------
        */

        $otp = random_int(100000, 999999);

        $expiresAt = Carbon::now()->addMinutes(5);


        /*
        |--------------------------------------------------------------------------
        | SAVE OTP
        |--------------------------------------------------------------------------
        */

        OtpVerification::create([
            'channel' => $channel,
            'to' => $to,
            'otp' => $otp,
            'expires_at' => $expiresAt,
            'used' => false,
            'attempts' => 0,
            'max_attempts' => 5,
        ]);


        /*
        |--------------------------------------------------------------------------
        | SEND OTP THROUGH EMAIL
        |--------------------------------------------------------------------------
        */

        if ($channel === 'email') {

            Mail::to($to)->send(
                new OtpMail($otp)
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SEND OTP THROUGH SMS
        |--------------------------------------------------------------------------
        */

        if ($channel === 'sms') {

            Notification::route('twilio', $to)
                ->notify(
                    new OtpSmsNotification($otp)
                );
        }


        /*
        |--------------------------------------------------------------------------
        | SAVE OTP SEND HISTORY
        |--------------------------------------------------------------------------
        */

        OtpHistory::create([
            'channel' => $channel,
            'recipient' => $to,
            'action' => 'send',
            'status' => 'success',
            'ip_address' => $ipAddress,
            'message' => 'OTP sent successfully.',
        ]);


        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        $requestsRemaining = max(
            0,
            self::MAX_OTP_REQUESTS - $rateLimit->request_count
        );

        return response()->json([
            'status' => true,
            'message' => 'OTP sent successfully.',
            'expires_in' => 300,
            'requests_remaining' => $requestsRemaining,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | VERIFY OTP
    |--------------------------------------------------------------------------
    */

    public function verifyOtp(Request $req)
    {
        $req->validate([
            'to' => 'required',
            'otp' => 'required|digits:6',
            'channel' => 'required|in:email,sms',
        ]);

        $to = $req->to;
        $channel = $req->channel;
        $ipAddress = $req->ip();


        /*
        |--------------------------------------------------------------------------
        | FIND LATEST VALID OTP
        |--------------------------------------------------------------------------
        */

        $record = OtpVerification::where('to', $to)
            ->where('channel', $channel)
            ->where('used', false)
            ->where('expires_at', '>=', now())
            ->orderBy('id', 'desc')
            ->first();


        /*
        |--------------------------------------------------------------------------
        | OTP NOT FOUND / EXPIRED
        |--------------------------------------------------------------------------
        */

        if (!$record) {

            OtpHistory::create([
                'channel' => $channel,
                'recipient' => $to,
                'action' => 'verify',
                'status' => 'failed',
                'ip_address' => $ipAddress,
                'message' => 'OTP expired or not found.',
            ]);

            return response()->json([
                'status' => false,
                'message' => 'OTP expired or not found.',
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | MAXIMUM ATTEMPTS
        |--------------------------------------------------------------------------
        */

        $maxAttempts = $record->max_attempts ?? 5;

        if ($record->attempts >= $maxAttempts) {

            OtpHistory::create([
                'channel' => $channel,
                'recipient' => $to,
                'action' => 'verify',
                'status' => 'blocked',
                'ip_address' => $ipAddress,
                'message' => 'Maximum OTP attempts exceeded.',
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Maximum OTP attempts exceeded.',
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | WRONG OTP
        |--------------------------------------------------------------------------
        */

        if ((string) $record->otp !== (string) $req->otp) {

            $record->attempts += 1;
            $record->save();

            $attemptMessage =
                'Invalid OTP. Attempts: ' .
                $record->attempts .
                '/' .
                $maxAttempts;


            OtpHistory::create([
                'channel' => $channel,
                'recipient' => $to,
                'action' => 'verify',
                'status' => 'failed',
                'ip_address' => $ipAddress,
                'message' => $attemptMessage,
            ]);


            /*
            |--------------------------------------------------------------------------
            | MAXIMUM ATTEMPTS REACHED
            |--------------------------------------------------------------------------
            */

            if ($record->attempts >= $maxAttempts) {

                OtpHistory::create([
                    'channel' => $channel,
                    'recipient' => $to,
                    'action' => 'verify',
                    'status' => 'blocked',
                    'ip_address' => $ipAddress,
                    'message' => 'Maximum OTP attempts reached.',
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Maximum OTP attempts exceeded.',
                ], 422);
            }


            return response()->json([
                'status' => false,
                'message' => $attemptMessage,
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | SUCCESSFUL OTP VERIFICATION
        |--------------------------------------------------------------------------
        */

        $record->used = true;
        $record->attempts = 0;
        $record->save();


        /*
        |--------------------------------------------------------------------------
        | SAVE SUCCESSFUL VERIFICATION
        |--------------------------------------------------------------------------
        */

        OtpHistory::create([
            'channel' => $channel,
            'recipient' => $to,
            'action' => 'verify',
            'status' => 'success',
            'ip_address' => $ipAddress,
            'message' => 'OTP verified successfully.',
        ]);


        return response()->json([
            'status' => true,
            'message' => 'OTP verified successfully.',
        ]);
    }
}