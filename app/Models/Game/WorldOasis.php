<?php

declare(strict_types=1);

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class WorldOasis extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'world_id',
        'x',
        'y',
        'type',
        'nature_garrison',
        'respawn_at',
    ];

    protected $table = 'oases';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'x' => 'integer',
            'y' => 'integer',
            'type' => 'integer',
            'nature_garrison' => 'array',
            'respawn_at' => 'datetime',
        ];
    }

    public function scopeDueForRespawn(Builder $query): Builder
    {
        return $query
            ->whereNotNull('respawn_at')
            ->where('respawn_at', '<=', now());
    }

    /**
     * Link the oasis back to its world so we can evaluate map speed.
     */
    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class, 'world_id');
    }

    /**
     * @param array<string, int> $garrison
     */
    public function assignNatureGarrison(array $garrison, Carbon $nextRespawnAt): void
    {
        $this->nature_garrison = $garrison;
        $this->respawn_at = $nextRespawnAt;
    }
}

