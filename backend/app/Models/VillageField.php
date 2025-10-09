<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VillageField extends Model
{
    use HasFactory;

    protected $fillable = [
        'village_id',
        'slot',
        'building_type_id',
        'level',
        'is_under_construction',
    ];

    protected $casts = [
        'slot' => 'integer',
        'level' => 'integer',
        'is_under_construction' => 'boolean',
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
