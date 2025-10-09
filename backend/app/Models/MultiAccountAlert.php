<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MultiAccountAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'primary_user_id',
        'conflict_user_id',
        'occurrences',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function primaryUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_user_id');
    }

    public function conflictUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conflict_user_id');
    }
}
