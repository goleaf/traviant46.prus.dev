<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeroItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'hero_id',
        'slot',
        'type',
        'rarity',
        'quantity',
        'is_equipped',
        'attributes',
        'acquired_at',
    ];

    protected $casts = [
        'hero_id' => 'integer',
        'quantity' => 'integer',
        'is_equipped' => 'boolean',
        'attributes' => 'array',
        'acquired_at' => 'datetime',
    ];

    public function hero(): BelongsTo
    {
        return $this->belongsTo(Hero::class);
    }
}
