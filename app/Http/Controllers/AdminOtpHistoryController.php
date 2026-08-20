<?php

namespace App\Http\Controllers;

use App\Models\OtpHistory;
use Illuminate\Http\Request;

class AdminOtpHistoryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | OTP History
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query = OtpHistory::query();

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where('recipient', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }


        /*
        |--------------------------------------------------------------------------
        | Channel Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('channel')) {

            $query->where(
                'channel',
                $request->channel
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Action Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('action')) {

            $query->where(
                'action',
                $request->action
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->status
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Latest First
        |--------------------------------------------------------------------------
        */

        $histories = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        $total = OtpHistory::count();

        $successful = OtpHistory::where(
            'status',
            'success'
        )->count();

        $failed = OtpHistory::where(
            'status',
            'failed'
        )->count();

        $blocked = OtpHistory::where(
            'status',
            'blocked'
        )->count();


        return view('admin.otp-history.index', compact(
            'histories',
            'total',
            'successful',
            'failed',
            'blocked'
        ));
    }
}