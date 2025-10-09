<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VillageResourceState extends Model
{
    use HasFactory;

    protected $fillable = [
        'village_id',
        'wood',
        'clay',
        'iron',
        'crop',
        'wood_production',
        'clay_production',
        'iron_production',
        'crop_production',
        'warehouse_capacity',
        'granary_capacity',
        'crop_consumption',
        'calculated_at',
    ];

    protected $casts = [
        'wood' => 'float',
        'clay' => 'float',
        'iron' => 'float',
        'crop' => 'float',
        'wood_production' => 'integer',
        'clay_production' => 'integer',
        'iron_production' => 'integer',
        'crop_production' => 'integer',
        'warehouse_capacity' => 'integer',
        'granary_capacity' => 'integer',
        'crop_consumption' => 'integer',
        'calculated_at' => 'datetime',
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
