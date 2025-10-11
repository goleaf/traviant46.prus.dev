<?php

namespace App\Models\Game;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Village extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'alliance_id',
        'name',
        'x_coordinate',
        'y_coordinate',
        'is_capital',
        'population',
        'culture_points',
        'storage',
        'production',
        'defense_bonus',
    ];

    protected $casts = [
        'is_capital' => 'boolean',
        'storage' => 'array',
        'production' => 'array',
        'defense_bonus' => 'array',
    ];

    protected $appends = [
        'coordinates',
        'total_population',
        'production_rates',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(VillageBuilding::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(VillageUnit::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(MovementOrder::class, 'origin_village_id');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(VillageResource::class);
    }

    public function getCoordinatesAttribute(): array
    {
        return [
            'x' => (int) $this->x_coordinate,
            'y' => (int) $this->y_coordinate,
        ];
    }

    public function getTotalPopulationAttribute(): int
    {
        $unitPopulation = $this->relationLoaded('units')
            ? $this->units->sum('quantity')
            : $this->units()->sum('quantity');

        return (int) $this->population + (int) $unitPopulation;
    }

    public function getProductionRatesAttribute(): array
    {
        $rates = is_array($this->production) ? $this->production : (array) $this->production;

        $resources = $this->relationLoaded('resources')
            ? $this->resources
            : $this->resources()->get();

        foreach ($resources as $resource) {
            $type = $resource->resource_type;
            $rates[$type] = ($rates[$type] ?? 0) + (int) $resource->production_per_hour;
        }

        return array_map('intval', $rates);
    }

    public function scopeCapital(Builder $query): Builder
    {
        return $query->where('is_capital', true);
    }

    public function scopeByCoordinates(Builder $query, int $x, int $y): Builder
    {
        return $query->where('x_coordinate', $x)->where('y_coordinate', $y);
    }

    public function scopeInRadius(Builder $query, int $x, int $y, int $radius): Builder
    {
        $radiusSquared = $radius * $radius;

        return $query->whereRaw(
            'POWER(x_coordinate - ?, 2) + POWER(y_coordinate - ?, 2) <= ?',
            [$x, $y, $radiusSquared]
        );
    }
}
