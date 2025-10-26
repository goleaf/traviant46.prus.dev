<?php

declare(strict_types=1);

namespace App\Livewire\Village;

use App\Models\Game\Village;
use App\Models\Game\VillageBuilding;
use App\Services\Game\VillageQueueService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Livewire port of the legacy `Dorf2Ctrl` infrastructure screen.
 *
 * Feature goals pulled forward from the controller:
 * - render the inner village slots (19–40) with level, building type, and
 *   queued upgrades highlighted for at-a-glance planning.
 * - surface the building queue the same way the batch template embedded the
 *   "currently building" widget near the minimap.
 * - keep sitter-restricted sessions read-only by driving data through
 *   container services instead of legacy global helpers.
 */
class Infrastructure extends Component
{
    private const SLOT_RANGE = [
        19, 20, 21, 22, 23, 24, 25, 26, 27, 28,
        29, 30, 31, 32, 33, 34, 35, 36, 37, 38,
        39, 40,
    ];

    public Village $village;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $slots = [];

    /**
     * @var array<string, mixed>
     */
    public array $queue = [];

    private VillageQueueService $queueService;

    public function boot(VillageQueueService $queueService): void
    {
        $this->queueService = $queueService;
    }

    public function mount(Village $village): void
    {
        $this->village = $village->loadMissing('buildings.buildable');
        $this->refreshInfrastructure();
    }

    public function refreshInfrastructure(): void
    {
        $this->village->refresh()->loadMissing('buildings.buildable');
        $this->queue = $this->queueService->summarize($this->village);
        $this->slots = $this->buildSlotSnapshots(collect($this->queue['entries'] ?? []));
        $this->dispatch('buildingQueue:refresh');
    }

    public function render(): View
    {
        return view('livewire.village.infrastructure');
    }

    /**
     * @param Collection<int, array<string, mixed>> $queueEntries
     * @return array<int, array<string, mixed>>
     */
    private function buildSlotSnapshots(Collection $queueEntries): array
    {
        $buildings = $this->village->buildings->keyBy('slot_number');

        return collect(self::SLOT_RANGE)
            ->map(function (int $slot) use ($buildings, $queueEntries): array {
                /** @var VillageBuilding|null $building */
                $building = $buildings->get($slot);
                $queued = $queueEntries->firstWhere('slot', $slot);

                $hasStructure = $building !== null && $building->building_type !== null;
                $name = $hasStructure
                    ? ($building->buildable?->name ?? __('Building :gid', ['gid' => $building->building_type]))
                    : __('Empty construction site');

                return [
                    'slot' => $slot,
                    'name' => $name,
                    'level' => $building?->level ?? 0,
                    'building_type' => $building?->building_type,
                    'queued_level' => $queued ? (int) $queued['target_level'] : null,
                    'queue_status' => $queued['status'] ?? null,
                    'is_active' => $queued['is_active'] ?? false,
                    'has_structure' => $hasStructure,
                ];
            })
            ->values()
            ->all();
    }
}
