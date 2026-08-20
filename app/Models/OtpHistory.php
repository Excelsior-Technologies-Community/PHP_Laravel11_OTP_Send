<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel',
        'recipient',
        'action',
        'status',
        'ip_address',
        'message',
    ];
}