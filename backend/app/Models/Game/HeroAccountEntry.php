<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeroAccountEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'hero_id',
        'reason',
        'gold_delta',
        'silver_delta',
        'details',
        'recorded_at',
    ];

    protected $casts = [
        'gold_delta' => 'integer',
        'silver_delta' => 'integer',
        'details' => 'array',
        'recorded_at' => 'datetime',
    ];

    public function hero(): BelongsTo
    {
        return $this->belongsTo(Hero::class);
    }
}
