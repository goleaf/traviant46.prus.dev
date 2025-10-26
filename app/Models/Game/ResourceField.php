<?php

declare(strict_types=1);

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $village_id
 * @property int $slot_number
 * @property string $kind
 * @property int $level
 * @property int $production_per_hour_cached
 */
class ResourceField extends Model
{
    use HasFactory;

    protected $fillable = [
        'village_id',
        'slot_number',
        'kind',
        'level',
        'production_per_hour_cached',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'village_id' => 'integer',
            'slot_number' => 'integer',
            'level' => 'integer',
            'production_per_hour_cached' => 'integer',
        ];
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
