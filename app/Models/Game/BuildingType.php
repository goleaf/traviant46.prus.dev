<?php

declare(strict_types=1);

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class BuildingType extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'gid',
        'slug',
        'name',
        'category',
        'max_level',
        'metadata',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'gid' => 'integer',
        'max_level' => 'integer',
        'metadata' => 'array',
    ];

    public function buildings(): MorphMany
    {
        return $this->morphMany(VillageBuilding::class, 'buildable');
    }

    public function maxLevel(): ?int
    {
        return $this->max_level;
    }

    public function canBuild(Building $building, int $levels = 1): bool
    {
        $maxLevel = $this->maxLevel();

        return $maxLevel === null || ($building->level + $levels) <= $maxLevel;
    }
}
