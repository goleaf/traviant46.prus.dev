<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Village extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'alliance_id',
        'name',
        'population',
        'tribe',
        'loyalty',
        'culture_points',
        'x',
        'y',
        'resources',
        'storage',
        'is_capital',
        'active',
        'last_raided_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $casts = [
        'resources' => 'array',
        'storage' => 'array',
        'is_capital' => 'boolean',
        'active' => 'boolean',
        'population' => 'integer',
        'tribe' => 'string',
        'loyalty' => 'float',
        'culture_points' => 'integer',
        'x' => 'integer',
        'y' => 'integer',
        'last_raided_at' => 'datetime',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'population' => 2,
        'tribe' => 'neutral',
        'loyalty' => 100,
        'culture_points' => 0,
        'resources' => '[]',
        'storage' => '[]',
        'is_capital' => false,
        'active' => true,
    ];

    protected static function booted(): void
    {
        static::creating(function (Village $village): void {
            $village->name = trim($village->name);
            $village->population = max(2, $village->population ?? 2);
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function alliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class);
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function outgoingAttacks(): HasMany
    {
        return $this->hasMany(Attack::class, 'origin_village_id');
    }

    public function incomingAttacks(): HasMany
    {
        return $this->hasMany(Attack::class, 'target_village_id');
    }

    public function hero(): HasOne
    {
        return $this->hasOne(Hero::class);
    }

    protected function coordinates(): Attribute
    {
        return Attribute::get(fn (): string => sprintf('%d|%d', $this->x, $this->y));
    }

    protected function lastRaidAgo(): Attribute
    {
        return Attribute::get(function (): ?int {
            if (!$this->last_raided_at instanceof Carbon) {
                return null;
            }

            return now()->diffInMinutes($this->last_raided_at);
        });
    }

    public function scopeCapital(Builder $query): Builder
    {
        return $query->where('is_capital', true);
    }

    public function scopeLocatedAt(Builder $query, int $x, int $y): Builder
    {
        return $query->where('x', $x)->where('y', $y);
    }

    public function scopeWithPopulationAbove(Builder $query, int $population): Builder
    {
        return $query->where('population', '>=', $population);
    }
}
