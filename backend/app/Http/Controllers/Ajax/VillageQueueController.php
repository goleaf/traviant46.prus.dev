<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Requests\QueueBuildingRequest;
use App\Models\Village;
use App\Services\Game\BuildingQueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VillageQueueController extends Controller
{
    public function __construct(
        protected BuildingQueueService $queueService,
    ) {
    }

    public function index(Request $request, Village $village): JsonResponse
    {
        $this->authorize('view', $village);

        $queue = $this->queueService->getQueueSnapshot($village)
            ->map(function (array $entry) {
                $building = $entry['building'] ?? null;

                return array_merge($entry, [
                    'building_name' => $building ? config("game.building_types.$building.name") : null,
                ]);
            })
            ->values();

        return response()->json([
            'data' => $queue,
        ]);
    }

    public function store(QueueBuildingRequest $request, Village $village): JsonResponse
    {
        $entry = $this->queueService->queueBuilding($village, $request->validated());

        return response()->json([
            'data' => [
                'id' => $entry->getKey(),
                'building' => $entry->building_key,
                'target_level' => (int) $entry->target_level,
                'slot' => (int) $entry->slot,
                'finishes_at' => optional($entry->finishes_at)->toIso8601String(),
                'cost' => $entry->cost ?? [],
            ],
        ], 201);
    }
}
