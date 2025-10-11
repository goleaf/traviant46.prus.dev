<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class VillageResource extends Model
{
    use HasFactory;

    public const TYPE_WOOD = 'wood';
    public const TYPE_CLAY = 'clay';
    public const TYPE_IRON = 'iron';
    public const TYPE_CROP = 'crop';

    protected $fillable = [
        'village_id',
        'resource_type',
        'current_stock',
        'storage_capacity',
        'production_per_hour',
        'last_calculated_at',
        'bonuses',
    ];

    protected $casts = [
        'current_stock' => 'decimal:4',
        'storage_capacity' => 'decimal:4',
        'production_per_hour' => 'decimal:4',
        'last_calculated_at' => 'datetime',
        'bonuses' => 'array',
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function markCalculated(Carbon $timestamp): void
    {
        $this->last_calculated_at = $timestamp;
        $this->save();
    }

    public static function resourceTypes(): array
    {
        return [
            self::TYPE_WOOD,
            self::TYPE_CLAY,
            self::TYPE_IRON,
            self::TYPE_CROP,
        ];
    }
}
