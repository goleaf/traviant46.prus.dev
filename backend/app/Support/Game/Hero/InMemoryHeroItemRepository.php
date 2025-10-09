<?php

namespace App\Support\Game\Hero;

use App\Contracts\Game\HeroItemRepository;
use App\ValueObjects\Game\Hero\HeroItem;

class InMemoryHeroItemRepository implements HeroItemRepository
{
    /** @var array<int,HeroItem> */
    private array $items = [];

    /**
     * @param array<int,HeroItem> $items
     */
    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->items[$item->id] = $item;
        }
    }

    public function add(HeroItem $item): void
    {
        $this->items[$item->id] = $item;
    }

    public function find(int $id): ?HeroItem
    {
        return $this->items[$id] ?? null;
    }
}
