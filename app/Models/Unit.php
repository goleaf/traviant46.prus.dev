<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Unit extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'village_id',
        'type',
        'quantity',
        'upkeep',
        'upgrades',
        'training_queue',
        'stats',
        'hero_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'upkeep' => 'integer',
        'upgrades' => 'array',
        'training_queue' => 'array',
        'stats' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (Unit $unit): void {
            $unit->type = strtolower($unit->type);
        });
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function hero(): BelongsTo
    {
        return $this->belongsTo(Hero::class);
    }

    public function attacks(): BelongsToMany
    {
        return $this->belongsToMany(Attack::class)
            ->withPivot(['quantity', 'casualties'])
            ->withTimestamps();
    }

    protected function combatPower(): Attribute
    {
        return Attribute::get(function (): int {
            $stats = $this->stats ?? [];

            $attack = (int) ($stats['attack'] ?? 0);
            $defenceInfantry = (int) ($stats['defence_infantry'] ?? 0);
            $defenceCavalry = (int) ($stats['defence_cavalry'] ?? 0);

            return (int) round(($attack + $defenceInfantry + $defenceCavalry) / 3);
        });
    }

    protected function isTraining(): Attribute
    {
        return Attribute::get(function (): bool {
            $queue = $this->training_queue ?? [];

            return is_array($queue) && count($queue) > 0;
        });
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', strtolower($type));
    }

    public function scopeCombatReady(Builder $query): Builder
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeStationedAt(Builder $query, int $villageId): Builder
    {
        return $query->where('village_id', $villageId);
    }
}
