<?php

namespace Tests\Unit\Services\Game;

use App\Services\Game\BuildingQueueService;
use App\ValueObjects\Game\Construction\BuildingQueueEntry;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class BuildingQueueServiceTest extends TestCase
{
    private BuildingQueueService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BuildingQueueService();
    }

    public function test_resolve_queues_filters_due_tasks(): void
    {
        $now = CarbonImmutable::create(2024, 1, 1, 12, 0, 0);
        $due = $now->subMinute();
        $future = $now->addHour();

        $resolution = $this->service->resolveQueues([
            new BuildingQueueEntry(1, 5, $due, false),
            new BuildingQueueEntry(1, 6, $future, false),
            new BuildingQueueEntry(1, 7, $due, true),
        ], [
            new BuildingQueueEntry(1, 5, $future, false),
            new BuildingQueueEntry(2, 8, $due, false),
        ], $now);

        $this->assertCount(1, $resolution->upgrades);
        $this->assertSame(5, $resolution->upgrades->first()->slot);
        $this->assertCount(1, $resolution->masterBuilder);
        $this->assertSame(7, $resolution->masterBuilder->first()->slot);
        $this->assertCount(1, $resolution->demolitions);
        $this->assertSame(8, $resolution->demolitions->first()->slot);
    }

    public function test_dispatch_invokes_callbacks(): void
    {
        $now = CarbonImmutable::now();

        $resolution = $this->service->resolveQueues([
            ['kid' => 1, 'building_field' => 3, 'commence' => $now->timestamp],
        ], [
            ['kid' => 2, 'building_field' => 9, 'commence' => $now->timestamp],
        ], $now);

        $upgrades = [];
        $demolitions = [];

        $this->service->dispatch(
            $resolution,
            function (BuildingQueueEntry $entry) use (&$upgrades) {
                $upgrades[] = $entry->payload;
            },
            function (BuildingQueueEntry $entry) use (&$demolitions) {
                $demolitions[] = $entry->payload;
            }
        );

        $this->assertCount(1, $upgrades);
        $this->assertSame(3, $upgrades[0]['building_field']);
        $this->assertCount(1, $demolitions);
        $this->assertSame(9, $demolitions[0]['building_field']);
    }
}
