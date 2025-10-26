<?php

declare(strict_types=1);

namespace App\Data\Game;

use App\Models\Game\ResourceField;
use App\Models\Game\Village;
use App\Models\Game\VillageBuilding;
use App\Models\Game\VillageUnit;
use Illuminate\Support\Collection;

/**
 * @phpstan-type ResourceFieldCollection Collection<int, ResourceField>
 * @phpstan-type VillageBuildingCollection Collection<int, VillageBuilding>
 * @phpstan-type VillageUnitCollection Collection<int, VillageUnit>
 */
final class VillageCreationData
{
    /**
     * @param ResourceFieldCollection $resourceFields
     * @param VillageBuildingCollection $infrastructure
     * @param VillageUnitCollection $garrison
     */
    public function __construct(
        public readonly Village $village,
        public readonly Collection $resourceFields,
        public readonly Collection $infrastructure,
        public readonly Collection $garrison,
    ) {}
}
