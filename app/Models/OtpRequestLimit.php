<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpRequestLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel',
        'recipient',
        'ip_address',
        'request_count',
        'window_started_at',
    ];

    protected $casts = [
        'window_started_at' => 'datetime',
    ];
}