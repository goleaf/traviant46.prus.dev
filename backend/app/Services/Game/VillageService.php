<?php

namespace App\Services\Game;

use App\Models\Village;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class VillageService
{
    public function __construct(
        protected BuildingQueueService $queueService,
    ) {
    }

    public function resolveActiveVillage(User $user, ?Village $requested = null): Village
    {
        if ($requested !== null) {
            Gate::forUser($user)->authorize('view', $requested);

            return $requested;
        }

        $village = $user->villages()->with('buildingQueueEntries')->first();

        if (! $village) {
            throw new ModelNotFoundException('No villages available for this account.');
        }

        Gate::forUser($user)->authorize('view', $village);

        return $village;
    }

    public function overview(User $user, Village $village): array
    {
        Gate::forUser($user)->authorize('view', $village);

        return [
            'village' => [
                'id' => $village->getKey(),
                'name' => $village->name ?? __('Unnamed village'),
                'population' => (int) ($village->population ?? 0),
                'coordinates' => [
                    'x' => (int) ($village->x ?? 0),
                    'y' => (int) ($village->y ?? 0),
                ],
            ],
            'production' => array_merge(config('game.default_production'), $village->resource_production ?? []),
            'storage' => [
                'warehouse' => (int) ($village->warehouse_capacity ?? 0),
                'granary' => (int) ($village->granary_capacity ?? 0),
                'resources' => array_merge(
                    config('game.default_production'),
                    $village->resources ?? []
                ),
            ],
            'queues' => $this->queueService->getQueueSnapshot($village)->toArray(),
        ];
    }

    public function buildingSummary(Village $village): Collection
    {
        return collect($village->resource_production ?? [])
            ->map(fn ($value, $resource) => [
                'resource' => $resource,
                'per_hour' => (int) $value,
            ]);
    }

    public function mapCoordinates(Village $village): array
    {
        return [
            'x' => (int) Arr::get($village->getAttributes(), 'x', 0),
            'y' => (int) Arr::get($village->getAttributes(), 'y', 0),
        ];
    }
}
