<?php

namespace App\Models\Game;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Village extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'population',
        'loyalty',
        'x_coordinate',
        'y_coordinate',
        'is_capital',
        'founded_at',
    ];

    protected $casts = [
        'is_capital' => 'boolean',
        'founded_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }

    public function buildingUpgrades(): HasMany
    {
        return $this->hasMany(VillageBuildingUpgrade::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(VillageUnit::class);
    }

    public function trainingBatches(): HasMany
    {
        return $this->hasMany(UnitTrainingBatch::class);
    }

    public function adventures(): HasMany
    {
        return $this->hasMany(Adventure::class);
    }
}
