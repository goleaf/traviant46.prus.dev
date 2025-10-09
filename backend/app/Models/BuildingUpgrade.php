<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuildingUpgrade extends Model
{
    use HasFactory;

    protected $table = 'building_upgrade_queue';

    protected $fillable = [
        'village_id',
        'slot',
        'building_type_id',
        'target_level',
        'queued_at',
        'completes_at',
        'is_master_builder',
    ];

    protected $casts = [
        'queued_at' => 'datetime',
        'completes_at' => 'datetime',
        'is_master_builder' => 'boolean',
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function buildingType(): BelongsTo
    {
        return $this->belongsTo(BuildingType::class);
    }
}
