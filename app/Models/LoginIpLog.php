<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginIpLog extends Model
{
    protected $fillable = [
        'user_id',
        'ip_address',
        'ip_address_numeric',
        'recorded_at',
    ];

    protected $casts = [
        'user_id' => 'int',
        'ip_address_numeric' => 'int',
        'recorded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
