<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VillageResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'village_id',
        'resource_type',
        'level',
        'production_per_hour',
        'storage_capacity',
        'bonuses',
    ];

    protected $casts = [
        'level' => 'integer',
        'production_per_hour' => 'integer',
        'storage_capacity' => 'integer',
        'bonuses' => 'array',
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
