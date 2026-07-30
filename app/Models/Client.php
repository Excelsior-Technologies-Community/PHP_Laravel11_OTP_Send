<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Client extends Authenticatable
{
    protected $fillable = [
        'name','email','phone','password'
    ];

    protected $hidden = [
        'password','login_otp','login_otp_attempts','login_otp_locked_until',
    ];

    protected $casts = [
        'login_otp_expires_at' => 'datetime',
        'login_otp_locked_until' => 'datetime',
    ];
}
