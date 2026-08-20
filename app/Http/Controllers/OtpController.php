<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Mail\OtpMail;
use App\Notifications\OtpSmsNotification;
use App\Models\OtpVerification;
use Carbon\Carbon;

class OtpController extends Controller
{
    public function sendOtp(Request $req)
    {
        $req->validate([
            'to' => 'required',
            'channel' => 'required|in:email,sms',
        ]);

        $to = $req->to;
        $channel = $req->channel;

        $otp = random_int(100000, 999999);

        $expiresAt = Carbon::now()->addMinutes(5);

        OtpVerification::create([
            'channel' => $channel,
            'to' => $to,
            'otp' => $otp,
            'expires_at' => $expiresAt,
        ]);

        if ($channel === 'email') {

            Mail::to($to)->send(
                new OtpMail($otp)
            );
        }

        if ($channel === 'sms') {

            Notification::route(
                'twilio',
                $to
            )->notify(
                new OtpSmsNotification($otp)
            );
        }

        return response()->json([
            'status' => true,
            'message' => 'OTP sent successfully',
        ]);
    }


    public function verifyOtp(Request $req)
    {
        $req->validate([
            'to' => 'required',
            'otp' => 'required|digits:6',
            'channel' => 'required|in:email,sms',
        ]);

        $record = OtpVerification::where(
            'to',
            $req->to
        )
            ->where(
                'channel',
                $req->channel
            )
            ->where(
                'used',
                false
            )
            ->where(
                'expires_at',
                '>=',
                now()
            )
            ->latest('id')
            ->first();


        if (!$record) {

            return response()->json([
                'status' => false,
                'message' =>
                'OTP expired or not found',
            ], 422);
        }


        if (
            $record->attempts >=
            ($record->max_attempts ?? 5)
        ) {

            return response()->json([
                'status' => false,
                'message' =>
                'Maximum OTP attempts exceeded',
            ], 422);
        }


        if (
            (string) $record->otp !==
            (string) $req->otp
        ) {

            $record->attempts += 1;

            $record->save();

            return response()->json([
                'status' => false,
                'message' =>
                'Invalid OTP. Attempts: ' .
                    $record->attempts .
                    '/' .
                    ($record->max_attempts ?? 5),
            ], 422);
        }


        $record->used = true;
        $record->attempts = 0;

        $record->save();


        return response()->json([
            'status' => true,
            'message' =>
            'OTP verified successfully',
        ]);
    }
}
