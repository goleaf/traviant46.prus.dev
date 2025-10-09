<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeroAdventure extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'hero_id',
        'origin_village_id',
        'target_village_id',
        'difficulty',
        'type',
        'status',
        'available_at',
        'started_at',
        'completed_at',
        'rewards',
        'context',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'origin_village_id' => 'integer',
        'target_village_id' => 'integer',
        'available_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'rewards' => 'array',
        'context' => 'array',
    ];

    public function hero(): BelongsTo
    {
        return $this->belongsTo(Hero::class);
    }
}
