<?php

namespace Tests\Unit\Game;

use App\Models\Game\UnitTrainingBatch;
use App\Models\Game\Village;
use App\Models\Game\VillageBuilding;
use App\Services\Game\TroopTrainingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TroopTrainingServiceTest extends TestCase
{
    use RefreshDatabase;

    private TroopTrainingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TroopTrainingService();
    }

    public function test_cannot_train_with_non_positive_quantity(): void
    {
        $village = Village::factory()->create();

        $this->assertFalse($this->service->canTrain($village, 1, 0, 'barracks'));
        $this->assertFalse($this->service->canTrain($village, 1, -5, 'barracks'));
    }

    public function test_requires_training_building(): void
    {
        $village = Village::factory()->create();

        $this->assertFalse($this->service->canTrain($village, 1, 5, 'barracks'));

        VillageBuilding::factory()->create([
            'village_id' => $village->id,
            'building_type' => 19,
            'level' => 3,
        ]);

        $this->assertTrue($this->service->canTrain($village->fresh(), 1, 5, 'barracks'));
    }

    public function test_calculate_training_time_accounts_for_building_level(): void
    {
        $village = Village::factory()->create();

        $building = VillageBuilding::factory()->create([
            'village_id' => $village->id,
            'building_type' => 19,
            'level' => 1,
        ]);

        $calculationLevelOne = $this->service->calculateTrainingTime($village->fresh(), 1, 10, 'barracks');

        $building->update(['level' => 10]);
        $calculationLevelTen = $this->service->calculateTrainingTime($village->fresh(), 1, 10, 'barracks');

        $this->assertGreaterThan($calculationLevelTen['per_unit_seconds'], $calculationLevelOne['per_unit_seconds']);
        $this->assertSame(10, $calculationLevelTen['building_level']);
    }

    public function test_train_creates_batch_and_chains_queue(): void
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $village = Village::factory()->create();

        VillageBuilding::factory()->create([
            'village_id' => $village->id,
            'building_type' => 19,
            'level' => 5,
        ]);

        $firstBatch = $this->service->train($village->fresh(), 1, 5, 'barracks');

        $this->assertSame(UnitTrainingBatch::STATUS_PENDING, $firstBatch->status);
        $this->assertSame(0, $firstBatch->queue_position);
        $this->assertSame('barracks', $firstBatch->training_building);
        $this->assertArrayHasKey('calculation', $firstBatch->metadata ?? []);
        $this->assertTrue($firstBatch->starts_at->equalTo(Carbon::now()));

        $secondBatch = $this->service->train($village->fresh(), 1, 3, 'barracks');

        $this->assertSame(1, $secondBatch->queue_position);
        $this->assertTrue($secondBatch->starts_at->equalTo($firstBatch->completes_at));
        $this->assertTrue($secondBatch->completes_at->greaterThan($secondBatch->starts_at));
    }
}
