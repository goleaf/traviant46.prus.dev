<?php

declare(strict_types=1);

namespace App\Repositories\Game;

use App\Models\Game\MapTile;
use App\Models\Game\World;
use DomainException;
use Illuminate\Support\Facades\Schema;

final class MapRepository
{
    public function claimCoordinates(World $world, int $x, int $y): ?MapTile
    {
        if (! Schema::hasTable('wdata')) {
            return null;
        }

        /** @var MapTile|null $tile */
        $tile = MapTile::query()
            ->where('x', $x)
            ->where('y', $y)
            ->first();

        if ($tile === null) {
            return null;
        }

        if ((bool) $tile->occupied) {
            throw new DomainException('Map tile is already occupied.');
        }

        $tile->occupied = true;
        $tile->save();

        return $tile;
    }
}
