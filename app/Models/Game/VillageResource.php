<?php

declare(strict_types=1);

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $village_id
 * @property string $resource_type
 * @property int $level
 * @property int $production_per_hour
 * @property int $storage_capacity
 * @property array<string, mixed>|null $bonuses
 * @property \Illuminate\Support\Carbon|null $last_collected_at
 */
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
        'last_collected_at',
    ];

    protected $casts = [
        'level' => 'integer',
        'production_per_hour' => 'integer',
        'storage_capacity' => 'integer',
        'bonuses' => 'array',
        'last_collected_at' => 'datetime',
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
