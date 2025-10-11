<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovementOrder extends Model
{
    use HasFactory;

    public const TYPE_TRADE = 'trade';
    public const TYPE_RETURN = 'return';

    public const STATUS_PENDING = 'pending';
    public const STATUS_EN_ROUTE = 'en_route';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'origin_village_id',
        'target_village_id',
        'movement_type',
        'status',
        'depart_at',
        'arrive_at',
        'payload',
    ];

    protected $casts = [
        'depart_at' => 'datetime',
        'arrive_at' => 'datetime',
        'payload' => 'array',
    ];

    public function scopeDueForArrival(Builder $query): Builder
    {
        return $query
            ->whereIn('movement_type', [self::TYPE_TRADE, self::TYPE_RETURN])
            ->where('status', self::STATUS_EN_ROUTE)
            ->whereNotNull('arrive_at')
            ->where('arrive_at', '<=', now());
    }
}
