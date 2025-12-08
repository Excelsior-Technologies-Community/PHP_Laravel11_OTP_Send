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
    // ----------------------------
    // SEND OTP (Email / SMS)
    // ----------------------------
    public function sendOtp(Request $req)
    {
        $req->validate([
            'to'      => 'required',
            'channel' => 'required|in:email,sms'
        ]);

        $to = $req->to;
        $channel = $req->channel;

        // Generate OTP
        $otp = rand(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(5);

        // Save to DB
        OtpVerification::create([
            'channel'     => $channel,
            'to'          => $to,
            'otp'         => $otp,
            'expires_at'  => $expiresAt
        ]);

        // ========= EMAIL =========
        if ($channel === 'email') {
            Mail::to($to)->send(new OtpMail($otp));
        }

        // ========= SMS (TWILIO) =========
        if ($channel === 'sms') {
            Notification::route('twilio', $to)
                ->notify(new OtpSmsNotification($otp));
        }

        return response()->json([
            'status'  => true,
            'message' => 'OTP sent successfully'
        ]);
    }


    // ----------------------------
    // VERIFY OTP
    // ----------------------------
    public function verifyOtp(Request $req)
    {
        $req->validate([
            'to'      => 'required',
            'otp'     => 'required',
            'channel' => 'required|in:email,sms'
        ]);

        $record = OtpVerification::where('to', $req->to)
            ->where('channel', $req->channel)
            ->where('used', false)
            ->where('expires_at', '>=', now())
            ->orderBy('id', 'desc')
            ->first();

        if (!$record) {
            return response()->json([
                'status'  => false,
                'message' => 'OTP expired or not found'
            ], 422);
        }

        if ($record->otp != $req->otp) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid OTP'
            ], 422);
        }

        // Mark OTP as used
        $record->used = true;
        $record->save();

        return response()->json([
            'status'  => true,
            'message' => 'OTP verified successfully'
        ]);
    }

}
