<?php

namespace Tests\Unit\Services\Game;

use App\Services\Game\ResourceTickService;
use App\ValueObjects\Game\Resources\VillageProduction;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class ResourceTickServiceTest extends TestCase
{
    private ResourceTickService $service;
    private CarbonImmutable $start;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ResourceTickService();
        $this->start = CarbonImmutable::create(2024, 1, 1, 0, 0, 0);
    }

    public function test_tick_updates_resources_based_on_elapsed_time(): void
    {
        $snapshot = new VillageProduction(
            villageId: 1,
            ownerId: 2,
            wood: 100,
            clay: 100,
            iron: 100,
            crop: 100,
            woodPerHour: 300,
            clayPerHour: 300,
            ironPerHour: 300,
            cropPerHour: 30,
            upkeep: 30,
            population: 50,
            maxStore: 1000,
            maxCrop: 1000,
            lastUpdatedAt: $this->start,
        );

        $result = $this->service->tick($snapshot, $this->start->addMinutes(30));

        $this->assertSame(250.0, $result->current->wood);
        $this->assertSame(250.0, $result->current->clay);
        $this->assertSame(250.0, $result->current->iron);
        $this->assertSame(75.0, $result->current->crop);
        $this->assertSame(150.0, $result->delta['wood']);
        $this->assertSame(-25.0, $result->delta['crop']);
    }

    public function test_tick_caps_resources_at_storage_limit(): void
    {
        $snapshot = new VillageProduction(
            villageId: 1,
            ownerId: 2,
            wood: 990,
            clay: 990,
            iron: 990,
            crop: 990,
            woodPerHour: 400,
            clayPerHour: 400,
            ironPerHour: 400,
            cropPerHour: 400,
            upkeep: 0,
            population: 0,
            maxStore: 1000,
            maxCrop: 1000,
            lastUpdatedAt: $this->start,
        );

        $result = $this->service->tick($snapshot, $this->start->addHour());

        $this->assertSame(1000.0, $result->current->wood);
        $this->assertSame(10.0, $result->delta['wood']);
        $this->assertSame(1000.0, $result->current->crop);
    }

    public function test_tick_handles_starvation_rules(): void
    {
        $snapshot = new VillageProduction(
            villageId: 1,
            ownerId: 1,
            wood: 0,
            clay: 0,
            iron: 0,
            crop: -50,
            woodPerHour: 0,
            clayPerHour: 0,
            ironPerHour: 0,
            cropPerHour: 0,
            upkeep: 0,
            population: 0,
            maxStore: 800,
            maxCrop: 1200,
            lastUpdatedAt: $this->start,
        );

        $result = $this->service->tick($snapshot, $this->start->addHour());
        $this->assertSame(800.0, $result->current->crop); // 2/3 of 1200

        $snapshot = new VillageProduction(
            villageId: 1,
            ownerId: 2,
            wood: 0,
            clay: 0,
            iron: 0,
            crop: -50,
            woodPerHour: 0,
            clayPerHour: 0,
            ironPerHour: 0,
            cropPerHour: 0,
            upkeep: 0,
            population: 0,
            maxStore: 800,
            maxCrop: 1200,
            lastUpdatedAt: $this->start,
        );

        $result = $this->service->tick($snapshot, $this->start->addHour(), false);
        $this->assertSame(1000.0, $result->current->crop);
    }
}
