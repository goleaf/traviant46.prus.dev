<?php

declare(strict_types=1);

namespace App\Models\Game;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int|null $legacy_kid
 * @property int|null $user_id
 * @property int|null $alliance_id
 * @property array{wood?: int, clay?: int, iron?: int, crop?: int}|null $resource_balances
 * @property array<string, mixed>|null $storage
 * @property array<string, mixed>|null $production
 * @property array{wall?: int, artifact?: int, hero?: int}|null $defense_bonus
 * @property \Illuminate\Support\Carbon|null $founded_at
 * @property \Illuminate\Support\Carbon|null $abandoned_at
 * @property \Illuminate\Support\Carbon|null $last_loyalty_change_at
 * @property-read array{x: int, y: int} $coordinates
 * @property-read int $total_population
 * @property-read array<string, int> $production_rates
 */
class Village extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'world_id',
        'legacy_kid',
        'user_id',
        'alliance_id',
        'watcher_user_id',
        'name',
        'x_coordinate',
        'y_coordinate',
        'terrain_type',
        'village_category',
        'is_capital',
        'is_wonder_village',
        'population',
        'loyalty',
        'culture_points',
        'resource_balances',
        'storage',
        'production',
        'defense_bonus',
        'founded_at',
        'abandoned_at',
        'last_loyalty_change_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_capital' => 'bool',
            'is_wonder_village' => 'bool',
            'resource_balances' => 'array',
            'storage' => 'array',
            'production' => 'array',
            'defense_bonus' => 'array',
            'founded_at' => 'datetime',
            'abandoned_at' => 'datetime',
            'last_loyalty_change_at' => 'datetime',
        ];
    }

    protected $appends = [
        'coordinates',
        'total_population',
        'production_rates',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function watcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'watcher_user_id');
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

    public function resourceFields(): HasMany
    {
        return $this->hasMany(ResourceField::class)->orderBy('slot_number');
    }

    public function incomingMovements(): HasMany
    {
        return $this->hasMany(MovementOrder::class, 'target_village_id');
    }

    public function stationedReinforcements(): HasMany
    {
        return $this->hasMany(ReinforcementGarrison::class, 'stationed_village_id');
    }

    public function dispatchedReinforcements(): HasMany
    {
        return $this->hasMany(ReinforcementGarrison::class, 'home_village_id');
    }

    public function marketOffers(): HasMany
    {
        return $this->hasMany(MarketOffer::class);
    }

    public function outgoingTrades(): HasMany
    {
        return $this->hasMany(Trade::class, 'origin');
    }

    public function incomingTrades(): HasMany
    {
        return $this->hasMany(Trade::class, 'target');
    }

    public function capturedUnits(): HasMany
    {
        return $this->hasMany(CapturedUnit::class, 'captor_village_id');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(VillageResource::class);
    }

    public function ownedOases(): BelongsToMany
    {
        return $this->belongsToMany(WorldOasis::class, 'oasis_ownerships', 'village_id', 'oasis_id');
    }

    public function buildingUpgrades(): HasMany
    {
        return $this->hasMany(VillageBuildingUpgrade::class);
    }

    public function buildQueues(): HasMany
    {
        return $this->hasMany(BuildQueue::class);
    }

    public function trainingBatches(): HasMany
    {
        return $this->hasMany(UnitTrainingBatch::class);
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

    public function scopeWonder(Builder $query): Builder
    {
        return $query->where('is_wonder_village', true);
    }

    public function scopeByCoordinates(Builder $query, int $x, int $y): Builder
    {
        return $query->where('x_coordinate', $x)->where('y_coordinate', $y);
    }

    public function scopeForLegacyKid(Builder $query, int $legacyKid): Builder
    {
        return $query->where('legacy_kid', $legacyKid);
    }

    public function scopeInRadius(Builder $query, int $x, int $y, int $radius): Builder
    {
        $radiusSquared = $radius * $radius;

        return $query->whereRaw(
            'POWER(x_coordinate - ?, 2) + POWER(y_coordinate - ?, 2) <= ?',
            [$x, $y, $radiusSquared],
        );
    }
}
