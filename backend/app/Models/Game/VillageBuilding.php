<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VillageBuilding extends Model
{
    use HasFactory;

    protected $fillable = [
        'village_id',
        'slot_number',
        'building_type',
        'level',
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
