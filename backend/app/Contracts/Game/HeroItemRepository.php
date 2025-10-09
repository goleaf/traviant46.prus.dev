<?php

namespace App\Contracts\Game;

use App\ValueObjects\Game\Hero\HeroItem;

interface HeroItemRepository
{
    public function find(int $id): ?HeroItem;
}
