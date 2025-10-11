<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hero extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'village_id',
        'home_village_id',
        'name',
        'level',
        'experience',
        'health',
        'energy',
        'status',
        'attributes',
        'equipment',
        'is_active',
        'last_moved_at',
        'last_revived_at',
    ];

    protected $casts = [
        'level' => 'integer',
        'experience' => 'integer',
        'health' => 'integer',
        'energy' => 'integer',
        'is_active' => 'boolean',
        'attributes' => 'array',
        'equipment' => 'array',
        'last_moved_at' => 'datetime',
        'last_revived_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
