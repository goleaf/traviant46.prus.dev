<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuildingQueueEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'village_id',
        'building_key',
        'target_level',
        'slot',
        'finishes_at',
        'cost',
    ];

    protected $casts = [
        'finishes_at' => 'datetime',
        'cost' => 'array',
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
