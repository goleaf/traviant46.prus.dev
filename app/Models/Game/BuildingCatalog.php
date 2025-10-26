<?php

declare(strict_types=1);

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuildingCatalog extends Model
{
    protected $table = 'building_catalog';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'building_type_id',
        'building_slug',
        'name',
        'prerequisites',
        'bonuses_per_level',
        'storage_capacity_per_level',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'building_type_id' => 'integer',
        'prerequisites' => 'array',
        'bonuses_per_level' => 'array',
        'storage_capacity_per_level' => 'array',
    ];

    public function buildingType(): BelongsTo
    {
        return $this->belongsTo(BuildingType::class);
    }
}
