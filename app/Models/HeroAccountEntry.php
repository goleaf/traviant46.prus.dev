<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeroAccountEntry extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'hero_id',
        'reason',
        'gold_delta',
        'silver_delta',
        'details',
        'recorded_at',
    ];

    /**
     * @var array<string, string>
     */
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
