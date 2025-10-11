<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class HeroInventory extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'hero_id',
        'capacity',
        'extra_slots',
        'last_water_bucket_used_at',
        'state',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'capacity' => 'integer',
        'extra_slots' => 'integer',
        'last_water_bucket_used_at' => 'datetime',
        'state' => 'array',
    ];

    public function hero(): BelongsTo
    {
        return $this->belongsTo(Hero::class);
    }

    public function totalSlots(): int
    {
        return (int) $this->capacity + (int) $this->extra_slots;
    }

    public function stateValue(string $key, mixed $default = null): mixed
    {
        $state = $this->getAttribute('state');

        return Arr::get(is_array($state) ? $state : [], $key, $default);
    }

    public function summarizeItems(Collection $items): array
    {
        $items = $items->filter(fn (HeroItem $item): bool => $item !== null);
        $backpackItems = $items->filter(fn (HeroItem $item): bool => ! $item->is_equipped);

        $usedSlots = (int) $backpackItems->sum(fn (HeroItem $item): int => $item->occupiedSlots());
        $totalSlots = $this->totalSlots();

        return [
            'total_slots' => $totalSlots,
            'used_slots' => min($usedSlots, $totalSlots),
            'free_slots' => max(0, $totalSlots - $usedSlots),
            'equipped_items' => $items->filter(fn (HeroItem $item): bool => $item->is_equipped)->count(),
            'backpack_items' => $backpackItems->count(),
            'backpack_quantity' => (int) $backpackItems->sum(fn (HeroItem $item): int => $item->quantity),
        ];
    }
}
