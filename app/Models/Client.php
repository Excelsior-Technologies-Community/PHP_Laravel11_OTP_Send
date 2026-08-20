<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    protected $hidden = [
        'password',
        'login_otp',
        'login_otp_attempts',
        'login_otp_locked_until',
    ];

    protected $casts = [
        'login_otp_expires_at' => 'datetime',
        'login_otp_locked_until' => 'datetime',
    ];

    public function otpLoginHistories(): HasMany
    {
        return $this->hasMany(OtpLoginHistory::class);
    }
}
