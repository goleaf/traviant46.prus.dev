<?php

namespace App\Models\Game;

use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use InvalidArgumentException;

class Building extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'village_id',
        'slot_number',
        'building_type',
        'buildable_type',
        'buildable_id',
        'level',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'level' => 'integer',
    ];

    /**
     * @var array<string, int>
     */
    protected $attributes = [
        'level' => 0,
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function buildable(): MorphTo
    {
        return $this->morphTo('buildable');
    }

    public function upgrade(int $levels = 1): self
    {
        if ($levels < 1) {
            throw new InvalidArgumentException('Upgrade levels must be at least 1.');
        }

        if (!$this->canBuild($levels)) {
            throw new DomainException('Building requirements are not satisfied for the requested upgrade.');
        }

        $this->level += $levels;
        $this->save();

        return $this->refresh();
    }

    public function demolish(?int $levels = null): self
    {
        if ($levels !== null && $levels < 1) {
            throw new InvalidArgumentException('Demolition levels must be null or at least 1.');
        }

        if ($levels === null || $levels >= $this->level) {
            $this->level = 0;
            $this->building_type = null;
            $this->buildable_type = null;
            $this->buildable_id = null;
        } else {
            $this->level -= $levels;
        }

        $this->save();

        return $this->refresh();
    }

    public function canBuild(int $levels = 1): bool
    {
        if ($levels < 1) {
            return false;
        }

        $buildable = $this->buildable;

        if ($buildable === null) {
            return true;
        }

        $targetLevel = $this->level + $levels;
        $maxLevel = $this->determineMaxLevel($buildable);

        if ($maxLevel !== null && $targetLevel > $maxLevel) {
            return false;
        }

        if (method_exists($buildable, 'canBuild')) {
            return (bool) $buildable->canBuild($this, $levels);
        }

        if (method_exists($buildable, 'meetsRequirements')) {
            return (bool) $buildable->meetsRequirements($this, $levels);
        }

        return true;
    }

    public function assignBuildable(?BuildingType $buildingType): void
    {
        if ($buildingType === null) {
            $this->buildable_type = null;
            $this->buildable_id = null;
            $this->building_type = null;

            return;
        }

        $this->buildable_type = $buildingType->getMorphClass();
        $this->buildable_id = $buildingType->getKey();
        $this->building_type = $buildingType->gid ?? $buildingType->getKey();
    }

    public function syncBuildableFromLegacy(?int $legacyType): void
    {
        if ($legacyType === null) {
            $this->assignBuildable(null);

            return;
        }

        $buildingType = BuildingType::query()
            ->where('gid', $legacyType)
            ->orWhere('id', $legacyType)
            ->first();

        if ($buildingType === null) {
            $this->building_type = $legacyType;

            return;
        }

        $this->assignBuildable($buildingType);
    }

    protected static function booted(): void
    {
        static::saving(function (Building $building): void {
            if ($building->buildable_type === null && $building->building_type !== null) {
                $building->syncBuildableFromLegacy($building->building_type);
            }
        });
    }

    private function determineMaxLevel(object $buildable): ?int
    {
        if (method_exists($buildable, 'maxLevel')) {
            $value = $buildable->maxLevel($this);

            return $value !== null ? (int) $value : null;
        }

        $value = data_get($buildable, 'max_level');

        if ($value === null && $buildable instanceof EloquentModel) {
            $value = data_get($buildable->getAttribute('attributes'), 'max_level');
        }

        return $value !== null ? (int) $value : null;
    }
}
