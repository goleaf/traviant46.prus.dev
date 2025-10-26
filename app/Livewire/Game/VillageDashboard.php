<?php

declare(strict_types=1);

namespace App\Livewire\Game;

use App\Events\Game\BuildCompleted;
use App\Events\Game\ResourcesProduced;
use App\Models\Game\Village;
use App\Services\Game\VillageQueueService;
use App\Services\Game\VillageResourceService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Livewire\Component;

class VillageDashboard extends Component
{
    use AuthorizesRequests;

    public Village $village;

    /**
     * @var array<string, float>
     */
    public array $balances = [];

    /**
     * @var array<string, float>
     */
    public array $production = [];

    /**
     * @var array<string, float>
     */
    public array $storage = [];

    public ?string $lastTickAt = null;

    public ?string $snapshotGeneratedAt = null;

    /**
     * Queue summary keyed by entries, active_entry, etc.
     *
     * @var array<string, mixed>
     */
    public array $queueSummary = [];

    public string $timezone = '';

    private string $channelName = '';

    private VillageResourceService $resourceService;

    private VillageQueueService $queueService;

    public function boot(
        VillageResourceService $resourceService,
        VillageQueueService $queueService,
    ): void {
        $this->resourceService = $resourceService;
        $this->queueService = $queueService;
    }

    public function mount(Village $village): void
    {
        $this->authorize('viewResources', $village);

        $this->village = $village->loadMissing([
            'resources',
            'buildingUpgrades.buildingType',
        ]);

        $this->channelName = $this->resolveBroadcastChannel();
        $this->timezone = auth()->user()?->timezone ?? config('app.timezone');

        $this->hydrateResources();
        $this->hydrateQueue();
    }

    public function getListeners(): array
    {
        $channel = $this->channelName ?: $this->resolveBroadcastChannel();

        return [
            "echo-private:{$channel},".ResourcesProduced::EVENT => 'handleResourcesProduced',
            "echo-private:{$channel},".BuildCompleted::EVENT => 'handleBuildCompleted',
        ];
    }

    public function refreshDashboard(): void
    {
        $this->reloadVillageState();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handleResourcesProduced(array $payload = []): void
    {
        if ($this->applyResourcePayload($payload)) {
            return;
        }

        $this->reloadVillageState();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handleBuildCompleted(array $payload = []): void
    {
        if (! $this->applyQueuePayload($payload)) {
            $this->reloadVillageQueue();
        }
    }

    public function render(): View
    {
        return view('livewire.game.village-dashboard');
    }

    private function resolveBroadcastChannel(): string
    {
        return sprintf('game.village.%d', $this->village->getKey());
    }

    private function hydrateResources(): void
    {
        $snapshot = $this->resourceService->snapshot($this->village);

        $this->balances = $this->normaliseResourceMap(
            is_array($this->village->resource_balances) ? $this->village->resource_balances : [],
        );
        $this->production = $this->normaliseResourceMap((array) ($snapshot['production'] ?? []));
        $this->storage = $this->normaliseResourceMap((array) ($snapshot['storage'] ?? []));

        $this->lastTickAt = $this->resolveLastTick()->toIso8601String();
        $this->snapshotGeneratedAt = (string) ($snapshot['generated_at'] ?? Carbon::now()->toIso8601String());
    }

    private function hydrateQueue(): void
    {
        $this->queueSummary = $this->queueService->summarize($this->village);
    }

    private function reloadVillageState(): void
    {
        $this->village->refresh();
        $this->village->load([
            'resources',
            'buildingUpgrades.buildingType',
        ]);

        $this->hydrateResources();
        $this->hydrateQueue();
    }

    private function reloadVillageQueue(): void
    {
        $this->village->load(['buildingUpgrades.buildingType']);
        $this->hydrateQueue();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function applyResourcePayload(array $payload): bool
    {
        $balances = Arr::get($payload, 'balances');
        $production = Arr::get($payload, 'production');
        $storage = Arr::get($payload, 'storage');

        if (! is_array($balances) || ! is_array($production) || ! is_array($storage)) {
            return false;
        }

        $this->balances = $this->normaliseResourceMap($balances);
        $this->production = $this->normaliseResourceMap($production);
        $this->storage = $this->normaliseResourceMap($storage);

        $lastTick = Arr::get($payload, 'last_tick_at');
        $this->lastTickAt = is_string($lastTick) ? $lastTick : Carbon::now()->toIso8601String();

        $generated = Arr::get($payload, 'generated_at');
        $this->snapshotGeneratedAt = is_string($generated)
            ? $generated
            : Carbon::now()->toIso8601String();

        return true;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function applyQueuePayload(array $payload): bool
    {
        $summary = Arr::get($payload, 'queue');

        if (! is_array($summary) || ! isset($summary['entries'])) {
            return false;
        }

        $this->queueSummary = $summary;

        return true;
    }

    private function resolveLastTick(): Carbon
    {
        $latest = $this->village->resources
            ->pluck('last_collected_at')
            ->filter()
            ->max();

        if ($latest instanceof Carbon) {
            return $latest;
        }

        if ($latest !== null) {
            return Carbon::parse($latest);
        }

        if ($this->village->updated_at instanceof Carbon) {
            return $this->village->updated_at;
        }

        return Carbon::now();
    }

    /**
     * @param array<string, float|int|null> $values
     * @return array<string, float>
     */
    private function normaliseResourceMap(array $values): array
    {
        $keys = ['wood', 'clay', 'iron', 'crop'];

        return collect($keys)
            ->mapWithKeys(static function (string $key) use ($values): array {
                $value = $values[$key] ?? 0;

                return [$key => is_numeric($value) ? (float) $value : 0.0];
            })
            ->all();
    }
}
