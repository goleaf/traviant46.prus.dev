<?php

declare(strict_types=1);

namespace App\Models\Game;

use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int|null $legacy_movement_id
 * @property int|null $user_id
 * @property int $origin_village_id
 * @property int $target_village_id
 * @property string $movement_type
 * @property string $status
 * @property array<string, mixed>|null $payload
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $depart_at
 * @property \Illuminate\Support\Carbon|null $arrive_at
 * @property \Illuminate\Support\Carbon|null $return_at
 * @property \Illuminate\Support\Carbon|null $processed_at
 */
class MovementOrder extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'legacy_movement_id',
        'user_id',
        'origin_village_id',
        'target_village_id',
        'movement_type',
        'mission',
        'status',
        'checksum',
        'depart_at',
        'arrive_at',
        'return_at',
        'processed_at',
        'payload',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'depart_at' => 'datetime',
            'arrive_at' => 'datetime',
            'return_at' => 'datetime',
            'processed_at' => 'datetime',
            'payload' => 'array',
            'metadata' => 'array',
        ];
    }

    public function originVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'origin_village_id');
    }

    public function targetVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'target_village_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeArrivingBetween(Builder $query, DateTimeInterface $from, DateTimeInterface $to): Builder
    {
        return $query->whereBetween('arrive_at', [$from, $to]);
    }

    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->getKey() : $user;

        return $query->where('user_id', $userId);
    }
}
