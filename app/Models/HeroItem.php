<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeroItem extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
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

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'is_equipped' => 'boolean',
        'attributes' => 'array',
        'acquired_at' => 'datetime',
    ];

    public function hero(): BelongsTo
    {
        return $this->belongsTo(Hero::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
