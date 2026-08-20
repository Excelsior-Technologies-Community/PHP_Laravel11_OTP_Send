<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpLoginHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'email',
        'phone',
        'channel',
        'status',
        'ip_address',
        'user_agent',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
