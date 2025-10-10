<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoExtendSubscription extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'auto_extend_subscriptions';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'feature_key',
        'feature_type',
        'starts_at',
        'last_checked_at',
        'expires_at',
        'is_enabled',
        'is_finished',
        'finished_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'feature_type' => 'integer',
        'starts_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_enabled' => 'boolean',
        'is_finished' => 'boolean',
        'finished_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
