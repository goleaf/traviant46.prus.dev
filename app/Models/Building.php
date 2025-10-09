<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Carbon;

class Building extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'village_id',
        'type',
        'level',
        'position',
        'queue',
        'effects',
        'is_under_construction',
        'finished_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'queue' => 'array',
        'effects' => 'array',
        'level' => 'integer',
        'position' => 'integer',
        'is_under_construction' => 'boolean',
        'finished_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Building $building): void {
            $building->type = strtolower($building->type);
        });
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function owner(): HasOneThrough
    {
        return $this->hasOneThrough(User::class, Village::class, 'id', 'id', 'village_id', 'user_id');
    }

    protected function isComplete(): Attribute
    {
        return Attribute::get(function (): bool {
            if ($this->is_under_construction) {
                return false;
            }

            if (!$this->finished_at instanceof Carbon) {
                return true;
            }

            return $this->finished_at->isPast();
        });
    }

    protected function queueLength(): Attribute
    {
        return Attribute::get(function (): int {
            $queue = $this->queue ?? [];

            return is_array($queue) ? count($queue) : 0;
        });
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', strtolower($type));
    }

    public function scopeReady(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder
                ->where('is_under_construction', false)
                ->orWhere(function (Builder $inner): void {
                    $inner
                        ->where('is_under_construction', true)
                        ->whereNotNull('finished_at')
                        ->where('finished_at', '<=', now());
                });
        });
    }

    public function scopeQueued(Builder $query): Builder
    {
        return $query->whereJsonLength('queue', '>', 0);
    }
}
