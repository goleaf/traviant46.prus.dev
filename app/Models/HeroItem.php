<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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

    public function scopeEquipped(Builder $query): Builder
    {
        return $query->where('is_equipped', true);
    }

    public function scopeInBackpack(Builder $query): Builder
    {
        return $query->where('is_equipped', false);
    }

    public function stackSize(): int
    {
        $attributes = $this->getAttribute('attributes') ?? [];
        $stackSize = (int) ($attributes['stack_size'] ?? 1);

        return max(1, $stackSize);
    }

    public function occupiedSlots(): int
    {
        $stackSize = $this->stackSize();
        $quantity = max(1, (int) $this->quantity);

        if ($stackSize <= 1) {
            return 1;
        }

        return (int) max(1, (int) ceil($quantity / $stackSize));
    }

    public function formattedAttributes(): array
    {
        $attributes = $this->getAttribute('attributes');

        if (! is_array($attributes) || $attributes === []) {
            return [];
        }

        return Collection::make($attributes)
            ->mapWithKeys(function (mixed $value, string|int $key): array {
                $label = is_string($key) ? Str::of($key)->headline()->toString() : (string) $key;

                return [$label => $this->formatAttributeValue($value)];
            })
            ->all();
    }

    public function displayName(): string
    {
        return Str::of($this->type)->replace('_', ' ')->headline()->toString();
    }

    protected function formatAttributeValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return Collection::make($value)
                ->map(function (mixed $item, string|int $key): string {
                    if (is_string($key)) {
                        return Str::of($key)->headline() . ': ' . $item;
                    }

                    return (string) $item;
                })
                ->implode(', ');
        }

        return (string) $value;
    }
}
