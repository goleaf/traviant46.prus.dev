<?php

declare(strict_types=1);

namespace App\Livewire\Game;

use App\Models\Game\BuildingCatalog;
use App\Models\Game\Village;
use App\Models\Game\VillageBuilding;
use App\Services\Game\BuildingPrerequisiteResolver;
use App\Services\Game\BuildingService;
use App\Services\Game\VillageQueueService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Component;
use Throwable;

class Buildings extends Component
{
    use AuthorizesRequests;

    public Village $village;

    /**
     * @var array<string, mixed>
     */
    public array $queue = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $buildings = [];

    /**
     * @var array<string, string>|null
     */
    public ?array $flashMessage = null;

    private VillageQueueService $queueService;

    private BuildingService $buildingService;

    private BuildingPrerequisiteResolver $prerequisiteResolver;

    /**
     * @var array<string, int>
     */
    private array $effectiveLevelsBySlug = [];

    /**
     * @var array<string, int>
     */
    private array $resourceLevels = [];

    /**
     * @var array<int, int>
     */
    private array $queuedTargetsBySlot = [];

    public function boot(
        VillageQueueService $queueService,
        BuildingService $buildingService,
        BuildingPrerequisiteResolver $prerequisiteResolver,
    ): void {
        $this->queueService = $queueService;
        $this->buildingService = $buildingService;
        $this->prerequisiteResolver = $prerequisiteResolver;
    }

    public function mount(Village $village): void
    {
        $this->authorize('viewInfrastructure', $village);

        $this->village = $village->loadMissing(['buildings.buildable', 'resources']);
        $this->refreshState();
    }

    public function refreshState(): void
    {
        $this->authorize('viewInfrastructure', $this->village);

        $this->village->refresh()->loadMissing(['buildings.buildable', 'resources']);

        $this->queue = $this->queueService->summarize($this->village);
        $this->buildings = $this->buildBuildingRows();
    }

    public function upgrade(int $buildingId): void
    {
        $this->authorize('viewInfrastructure', $this->village);

        $this->village->refresh()->loadMissing(['buildings.buildable', 'resources']);
        $this->queue = $this->queueService->summarize($this->village);
        $this->buildings = $this->buildBuildingRows();

        /** @var VillageBuilding|null $building */
        $building = $this->village->buildings->firstWhere('id', $buildingId);

        if ($building === null) {
            return;
        }

        $snapshot = collect($this->buildings)->firstWhere('id', $buildingId);

        if (! is_array($snapshot)) {
            return;
        }

        if (! $snapshot['can_upgrade']) {
            $this->flashMessage = [
                'type' => 'info',
                'message' => $snapshot['disabled_reason'] ?? __('Cannot upgrade this building right now.'),
            ];

            return;
        }

        $mainBuildingLevel = $this->effectiveLevelsBySlug['main-building'] ?? 0;

        try {
            $this->buildingService->upgrade(
                $this->village,
                $building,
                levels: 1,
                context: [
                    'main_building_level' => $mainBuildingLevel,
                ],
            );
        } catch (Throwable $exception) {
            report($exception);

            $this->flashMessage = [
                'type' => 'error',
                'message' => __('Failed to queue upgrade. Please try again.'),
            ];

            return;
        }

        $this->flashMessage = [
            'type' => 'success',
            'message' => __('Upgrade queued successfully.'),
        ];
        $this->refreshState();
        $this->dispatch('buildingQueue:refresh');
    }

    public function render(): View
    {
        return view('livewire.game.buildings', [
            'buildings' => $this->buildings,
            'queue' => $this->queue,
            'flashMessage' => $this->flashMessage,
            'village' => $this->village,
        ]);
    }

    public function dismissFlash(): void
    {
        $this->flashMessage = null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildBuildingRows(): array
    {
        $buildings = $this->village->buildings
            ->filter(static fn (VillageBuilding $building) => $building->buildable !== null)
            ->sortBy('slot_number')
            ->values();

        $slugs = $buildings
            ->map(static fn (VillageBuilding $building) => $building->buildable?->slug)
            ->filter()
            ->unique()
            ->values();

        $catalogItems = $slugs->isNotEmpty()
            ? BuildingCatalog::query()
                ->whereIn('building_slug', $slugs->all())
                ->get()
                ->keyBy('building_slug')
            : collect();

        $this->queuedTargetsBySlot = $this->mapQueuedTargetsBySlot();
        $this->resourceLevels = $this->mapResourceLevels();
        $this->effectiveLevelsBySlug = $this->calculateEffectiveLevelsBySlug($buildings);

        return $buildings
            ->map(function (VillageBuilding $building) use ($catalogItems): array {
                $slug = $building->buildable?->slug;
                $catalog = $slug !== null ? $catalogItems->get($slug) : null;

                $currentLevel = (int) $building->level;
                $effectiveLevel = $this->resolveEffectiveLevel($building);
                $maxLevel = $building->buildable?->maxLevel();
                $queuedTarget = $this->queuedTargetsBySlot[$building->slot_number] ?? null;

                $nextLevel = $maxLevel !== null && $effectiveLevel >= $maxLevel
                    ? null
                    : $effectiveLevel + 1;

                if ($maxLevel !== null && $nextLevel !== null && $nextLevel > $maxLevel) {
                    $nextLevel = null;
                }

                $prerequisites = $this->evaluatePrerequisites($catalog);
                $prerequisitesCollection = collect($prerequisites);
                $prerequisitesMet = $prerequisitesCollection->every(
                    static fn (array $prerequisite): bool => $prerequisite['met'] === true,
                );
                $unmetSummaries = $prerequisitesCollection
                    ->whereStrict('met', false)
                    ->pluck('summary')
                    ->filter()
                    ->values();

                $canUpgrade = $nextLevel !== null
                    && $prerequisitesMet
                    && $this->buildingService->canUpgrade($this->village, $building, 1);

                $disabledReason = null;

                if ($nextLevel === null) {
                    $disabledReason = __('Building has reached its maximum level.');
                } elseif (! $prerequisitesMet) {
                    $disabledReason = $unmetSummaries->isNotEmpty()
                        ? $unmetSummaries->implode(', ')
                        : __('Prerequisites are not satisfied.');
                } elseif (! $canUpgrade) {
                    $disabledReason = __('Cannot upgrade while another upgrade is pending.');
                }

                $nextLevelBonus = $nextLevel !== null ? $this->formatNextLevelBonus($catalog, $nextLevel, $slug) : null;

                return [
                    'id' => (int) $building->getKey(),
                    'slot' => (int) $building->slot_number,
                    'slug' => $slug,
                    'name' => $building->buildable?->name ?? __('Building :gid', ['gid' => $building->building_type ?? '?']),
                    'level' => $currentLevel,
                    'effective_level' => $effectiveLevel,
                    'queued_target_level' => $queuedTarget,
                    'max_level' => $maxLevel,
                    'next_level' => $nextLevel,
                    'next_level_bonus' => $nextLevelBonus,
                    'prerequisites' => $prerequisites,
                    'can_upgrade' => $canUpgrade,
                    'disabled_reason' => $disabledReason,
                ];
            })
            ->values()
            ->all();
    }

    private function resolveEffectiveLevel(VillageBuilding $building): int
    {
        $queuedTarget = $this->queuedTargetsBySlot[$building->slot_number] ?? null;

        return max((int) $building->level, $queuedTarget ?? 0);
    }

    /**
     * @return array<string, int>
     */
    private function calculateEffectiveLevelsBySlug(Collection $buildings): array
    {
        $levels = [];

        $buildings->each(function (VillageBuilding $building) use (&$levels): void {
            $slug = $building->buildable?->slug;

            if ($slug === null) {
                return;
            }

            $effectiveLevel = $this->resolveEffectiveLevel($building);
            $levels[$slug] = max($levels[$slug] ?? 0, $effectiveLevel);
        });

        return $levels;
    }

    /**
     * @return array<int, int>
     */
    private function mapQueuedTargetsBySlot(): array
    {
        return collect($this->queue['entries'] ?? [])
            ->reduce(function (array $carry, array $entry): array {
                $slot = isset($entry['slot']) ? (int) $entry['slot'] : null;
                $targetLevel = isset($entry['target_level']) ? (int) $entry['target_level'] : null;

                if ($slot === null || $targetLevel === null) {
                    return $carry;
                }

                $carry[$slot] = max($carry[$slot] ?? 0, $targetLevel);

                return $carry;
            }, []);
    }

    /**
     * @return array<string, int>
     */
    private function mapResourceLevels(): array
    {
        return $this->village->resources
            ->mapWithKeys(static function ($resource): array {
                $type = (string) $resource->resource_type;
                $level = (int) $resource->level;

                return [$type => $level];
            })
            ->all();
    }

    /**
     * @return array<int, array{
     *     type: string,
     *     slug: string,
     *     name: string,
     *     required_level: int,
     *     current_level: int,
     *     met: bool,
     *     summary: string
     * }>
     */
    private function evaluatePrerequisites(?BuildingCatalog $catalog): array
    {
        if ($catalog === null) {
            return [];
        }

        return $this->prerequisiteResolver->resolve($catalog, [
            'effective_building_levels' => $this->effectiveLevelsBySlug,
            'resource_levels' => $this->resourceLevels,
        ]);
    }

    private function formatNextLevelBonus(?BuildingCatalog $catalog, int $nextLevel, ?string $slug): ?string
    {
        if ($catalog === null) {
            return null;
        }

        $segments = [];

        $bonus = $catalog->bonuses_per_level[$nextLevel] ?? null;

        if (is_numeric($bonus)) {
            $resourceLabel = match ($slug) {
                'sawmill' => __('wood'),
                'iron-foundry' => __('iron'),
                'grain-mill' => __('crop'),
                'bakery' => __('crop'),
                default => __('production'),
            };

            $segments[] = __('+:value% :resource output', [
                'value' => (int) $bonus,
                'resource' => $resourceLabel,
            ]);
        }

        $capacity = $catalog->storage_capacity_per_level[$nextLevel] ?? null;

        if (is_numeric($capacity)) {
            // Surface the absolute storage increase so players understand how many resources the next level can hold.
            $segments[] = __('Storage capacity: :value resources', ['value' => number_format((int) $capacity)]);
        }

        if ($segments === []) {
            return null;
        }

        return __('Next level bonus: :details', ['details' => implode(' • ', $segments)]);
    }
}
