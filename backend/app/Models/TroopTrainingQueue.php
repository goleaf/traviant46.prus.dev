<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TroopTrainingQueue extends Model
{
    use HasFactory;

    protected $fillable = [
        'village_id',
        'troop_type_id',
        'building_slot',
        'amount',
        'training_started_at',
        'training_ends_at',
    ];

    protected $casts = [
        'building_slot' => 'integer',
        'amount' => 'integer',
        'training_started_at' => 'datetime',
        'training_ends_at' => 'datetime',
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
