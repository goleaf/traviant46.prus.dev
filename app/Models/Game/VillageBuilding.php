<?php

declare(strict_types=1);

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VillageBuilding extends Model
{
    use HasFactory;

    /**
     * @var list<string>
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
        'village_id' => 'integer',
        'slot_number' => 'integer',
        'building_type' => 'integer',
        'buildable_id' => 'integer',
        'level' => 'integer',
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function buildable(): MorphTo
    {
        return $this->morphTo('buildable');
    }

    public function buildingType(): BelongsTo
    {
        return $this->belongsTo(BuildingType::class, 'building_type', 'gid');
    }

    public function syncBuildableFromLegacy(?int $legacyType): void
    {
        if ($legacyType === null) {
            $this->buildable_type = null;
            $this->buildable_id = null;
            $this->building_type = null;

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

        $this->buildable_type = $buildingType->getMorphClass();
        $this->buildable_id = $buildingType->getKey();
        $this->building_type = $buildingType->gid ?? $buildingType->getKey();
    }
}
