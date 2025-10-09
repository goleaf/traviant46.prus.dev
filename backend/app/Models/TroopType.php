<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TroopType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'tribe',
        'training_building_type_id',
        'attack',
        'defense_infantry',
        'defense_cavalry',
        'speed',
        'carry_capacity',
        'crop_consumption',
        'cost',
    ];

    protected $casts = [
        'attack' => 'integer',
        'defense_infantry' => 'integer',
        'defense_cavalry' => 'integer',
        'speed' => 'integer',
        'carry_capacity' => 'integer',
        'crop_consumption' => 'integer',
        'cost' => 'array',
    ];

    public function trainingBuilding(): BelongsTo
    {
        return $this->belongsTo(BuildingType::class, 'training_building_type_id');
    }

    public function garrisons(): HasMany
    {
        return $this->hasMany(VillageTroop::class);
    }

    public function trainingQueues(): HasMany
    {
        return $this->hasMany(TroopTrainingQueue::class);
    }
}
