<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VillageTroop extends Model
{
    use HasFactory;

    protected $fillable = [
        'village_id',
        'troop_type_id',
        'stationed',
        'training',
        'away',
    ];

    protected $casts = [
        'stationed' => 'integer',
        'training' => 'integer',
        'away' => 'integer',
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function troopType(): BelongsTo
    {
        return $this->belongsTo(TroopType::class);
    }
}
