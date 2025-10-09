<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuildingType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'max_level',
        'is_resource_field',
        'base_cost',
        'production',
        'bonuses',
    ];

    protected $casts = [
        'max_level' => 'integer',
        'is_resource_field' => 'boolean',
        'base_cost' => 'array',
        'production' => 'array',
        'bonuses' => 'array',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(VillageField::class);
    }

    public function upgrades(): HasMany
    {
        return $this->hasMany(BuildingUpgrade::class);
    }

    public function troopTypes(): HasMany
    {
        return $this->hasMany(TroopType::class, 'training_building_type_id');
    }
}
