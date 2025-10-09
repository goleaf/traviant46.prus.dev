<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FarmList extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'source_village_id',
        'name',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function sourceVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'source_village_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(FarmListEntry::class);
    }
}
