<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Client extends Authenticatable
{
    protected $fillable = [
        'name','email','phone','password'
    ];

    protected $hidden = [
        'password','login_otp',
    ];

    protected $casts = [
        'login_otp_expires_at' => 'datetime',
    ];
}
