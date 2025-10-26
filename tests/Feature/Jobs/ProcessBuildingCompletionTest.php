<?php

declare(strict_types=1);

use App\Enums\Game\BuildQueueState;
use App\Enums\Game\VillageBuildingUpgradeStatus;
use App\Jobs\ProcessBuildingCompletion;
use App\Models\Game\BuildingType;
use App\Models\Game\BuildQueue;
use App\Models\Game\Village;
use App\Models\Game\VillageBuildingUpgrade;
use App\Services\Game\BuildingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('recomputes build queue finish time when main building upgrade completes', function (): void {
    Carbon::setTestNow('2025-05-05 12:00:00');

    $village = Village::factory()->create();

    BuildingType::factory()->create([
        'gid' => 15,
        'name' => 'Main Building',
        'category' => 'infrastructure',
        'max_level' => 20,
    ]);

    config()->set('travian.settings.game.speed', 1.0);

    DB::table('buildings')->insert([
        'village_id' => $village->getKey(),
        'building_type' => 15,
        'position' => 1,
        'level' => 5,
    ]);

    $service = new BuildingService;
    $initialDuration = $service->calculateBuildTime(1, 1, 5);

    $queue = BuildQueue::create([
        'village_id' => $village->getKey(),
        'building_type' => 1,
        'target_level' => 1,
        'state' => BuildQueueState::Pending,
        'finishes_at' => Carbon::now()->addSeconds($initialDuration),
    ]);

    VillageBuildingUpgrade::create([
        'village_id' => $village->getKey(),
        'slot_number' => 25,
        'building_type' => 15,
        'current_level' => 5,
        'target_level' => 6,
        'queue_position' => 0,
        'status' => VillageBuildingUpgradeStatus::Pending,
        'metadata' => [],
        'queued_at' => Carbon::now()->subHour(),
        'starts_at' => Carbon::now()->subHour(),
        'completes_at' => Carbon::now()->subMinute(),
    ]);

    $job = new ProcessBuildingCompletion(chunkSize: 10);
    $job->handle();

    $queue->refresh();

    $expectedDuration = $service->calculateBuildTime(1, 1, 6);
    expect($queue->finishes_at->equalTo(Carbon::now()->addSeconds($expectedDuration)))->toBeTrue();

    Carbon::setTestNow();
});
