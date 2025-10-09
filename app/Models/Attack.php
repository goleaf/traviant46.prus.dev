<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

class Attack extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'attacker_id',
        'defender_id',
        'origin_village_id',
        'target_village_id',
        'type',
        'status',
        'payload',
        'results',
        'launched_at',
        'arrives_at',
        'travel_time',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
        'results' => 'array',
        'launched_at' => 'datetime',
        'arrives_at' => 'datetime',
        'travel_time' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Attack $attack): void {
            $attack->status ??= 'marching';
            $attack->type ??= 'raid';
            $attack->launched_at ??= now();
        });
    }

    public function attacker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attacker_id');
    }

    public function defender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'defender_id');
    }

    public function originVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'origin_village_id');
    }

    public function targetVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'target_village_id');
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class)
            ->withPivot(['quantity', 'casualties'])
            ->withTimestamps();
    }

    protected function isRaid(): Attribute
    {
        return Attribute::get(fn (): bool => $this->type === 'raid');
    }

    protected function travelProgress(): Attribute
    {
        return Attribute::get(function (): float {
            if (!$this->launched_at instanceof Carbon || !$this->arrives_at instanceof Carbon) {
                return 0.0;
            }

            $total = $this->launched_at->diffInSeconds($this->arrives_at, false);

            if ($total <= 0) {
                return 1.0;
            }

            $elapsed = $this->launched_at->diffInSeconds(now(), false);

            return max(0.0, min(1.0, $elapsed / $total));
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['marching', 'returning']);
    }

    public function scopeIncomingTo(Builder $query, int $villageId): Builder
    {
        return $query->where('target_village_id', $villageId);
    }

    public function scopeOutgoingFrom(Builder $query, int $villageId): Builder
    {
        return $query->where('origin_village_id', $villageId);
    }

    public function scopeLandingBetween(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('arrives_at', [$from, $to]);
    }
}
