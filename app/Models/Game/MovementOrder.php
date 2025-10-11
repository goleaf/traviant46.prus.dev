<?php

namespace App\Models\Game;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovementOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'origin_village_id',
        'target_village_id',
        'movement_type',
        'status',
        'depart_at',
        'arrive_at',
        'payload',
    ];

    protected $casts = [
        'depart_at' => 'datetime',
        'arrive_at' => 'datetime',
        'payload' => 'array',
    ];

    public function originVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'origin_village_id');
    }

    public function targetVillage(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'target_village_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
