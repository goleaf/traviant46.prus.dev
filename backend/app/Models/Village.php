<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Village extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'name',
        'x_coordinate',
        'y_coordinate',
        'field_type',
        'is_capital',
        'is_world_wonder',
        'is_oasis',
        'population',
        'culture_points',
        'loyalty',
        'loyalty_updated_at',
        'founded_at',
    ];

    protected $casts = [
        'is_capital' => 'boolean',
        'is_world_wonder' => 'boolean',
        'is_oasis' => 'boolean',
        'loyalty' => 'decimal:2',
        'loyalty_updated_at' => 'datetime',
        'founded_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(VillageField::class);
    }

    public function resourceState(): HasOne
    {
        return $this->hasOne(VillageResourceState::class);
    }

    public function troopGarrisons(): HasMany
    {
        return $this->hasMany(VillageTroop::class);
    }

    public function trainingQueues(): HasMany
    {
        return $this->hasMany(TroopTrainingQueue::class);
    }

    public function outgoingMovements(): HasMany
    {
        return $this->hasMany(TroopMovement::class, 'origin_village_id');
    }

    public function incomingMovements(): HasMany
    {
        return $this->hasMany(TroopMovement::class, 'target_village_id');
    }

    public function heroStates(): HasMany
    {
        return $this->hasMany(HeroState::class, 'current_village_id');
    }

    public function buildingQueue(): HasMany
    {
        return $this->hasMany(BuildingUpgrade::class);
    }

    public function farmLists(): HasMany
    {
        return $this->hasMany(FarmList::class, 'source_village_id');
    }
}
