<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuthEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthEvent extends Model
{
    /** @use HasFactory<\Database\Factories\AuthEventFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_type',
        'identifier',
        'ip_address',
        'user_agent',
        'meta',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => AuthEventType::class,
            'meta' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
