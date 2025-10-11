<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wonder extends Model
{
    use HasFactory;

    protected $fillable = [
        'village_id',
        'owner_id',
        'level',
        'completed_at',
    ];

    protected $casts = [
        'level' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }
}
