<?php

namespace App\Livewire\Hero;

use App\Models\Hero;
use App\Models\HeroInventory as HeroInventoryModel;
use App\Models\HeroItem;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;

class HeroInventory extends Component
{
    public ?Hero $hero = null;

    public function mount(?Hero $hero = null, ?int $heroId = null): void
    {
        $this->hero = $this->resolveHero($hero, $heroId);
    }

    public function render(): View
    {
        $hero = $this->hero;
        $inventory = $hero?->inventory;
        $items = Collection::make($hero?->items ?? []);

        $summary = $inventory instanceof HeroInventoryModel
            ? $inventory->summarizeItems($items)
            : $this->emptySummary();

        return view('livewire.hero.hero-inventory', [
            'hero' => $hero,
            'inventory' => $inventory,
            'summary' => $summary,
            'equippedItems' => $this->equippedItems($items),
            'backpackItems' => $this->backpackItems($items),
        ]);
    }

    protected function resolveHero(?Hero $hero, ?int $heroId = null): ?Hero
    {
        if ($hero instanceof Hero) {
            return $hero->loadMissing($this->eagerLoads());
        }

        $query = Hero::query()->with($this->eagerLoads());

        if ($heroId !== null) {
            return $query->find($heroId);
        }

        return $query->first();
    }

    protected function eagerLoads(): array
    {
        return [
            'inventory',
            'items' => fn (Builder $builder): Builder => $builder
                ->orderByDesc('is_equipped')
                ->orderBy('slot')
                ->orderBy('rarity')
                ->orderBy('type'),
        ];
    }

    protected function equippedItems(Collection $items): Collection
    {
        return $items
            ->filter(fn (HeroItem $item): bool => $item->is_equipped)
            ->sortBy(fn (HeroItem $item): string => sprintf('%s-%s-%s', $item->slot, $item->rarity, $item->type))
            ->groupBy('slot')
            ->map(fn (Collection $slotItems): Collection => $slotItems->values());
    }

    protected function backpackItems(Collection $items): Collection
    {
        return $items
            ->filter(fn (HeroItem $item): bool => ! $item->is_equipped)
            ->sortBy(fn (HeroItem $item): string => sprintf('%s-%s-%s', $item->slot, $item->rarity, $item->type))
            ->values();
    }

    /**
     * @return array<string, int>
     */
    protected function emptySummary(): array
    {
        return [
            'total_slots' => 0,
            'used_slots' => 0,
            'free_slots' => 0,
            'equipped_items' => 0,
            'backpack_items' => 0,
            'backpack_quantity' => 0,
        ];
    }
}
