<?php

declare(strict_types=1);

namespace App\Models\Game;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|null $legacy_enforcement_id
 * @property int|null $owner_user_id
 * @property int|null $home_village_id
 * @property int $stationed_village_id
 * @property array<string, int>|null $unit_composition
 * @property array<string, mixed>|null $metadata
 * @property bool $is_active
 * @property int $upkeep
 * @property \Illuminate\Support\Carbon|null $deployed_at
 * @property \Illuminate\Support\Carbon|null $last_synced_at
 */
class ReinforcementGarrison extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'legacy_enforcement_id',
        'owner_user_id',
        'home_village_id',
        'stationed_village_id',
        'supporting_alliance_id',
        'unit_composition',
        'upkeep',
        'is_active',
        'deployed_at',
        'last_synced_at',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_composition' => 'array',
            'metadata' => 'array',
            'deployed_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'is_active' => 'bool',
            'upkeep' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function homeVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'home_village_id');
    }

    public function stationedVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'stationed_village_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
