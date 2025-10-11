<?php

namespace Tests\Unit\Game;

use App\Models\Game\UnitTrainingBatch;
use App\Models\Game\Village;
use App\Models\Game\VillageBuilding;
use App\Services\Game\TroopTrainingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TroopTrainingServiceTest extends TestCase
{
    use RefreshDatabase;

    private TroopTrainingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        if (!class_exists(VillageBuilding::class)) {
            eval(<<<'PHP'
                namespace App\Models\Game;

                use Illuminate\Database\Eloquent\Factories\HasFactory;
                use Illuminate\Database\Eloquent\Model;

                class VillageBuilding extends Model
                {
                    use HasFactory;

                    protected $table = 'village_buildings';

                    protected $fillable = [
                        'village_id',
                        'slot_number',
                        'building_type',
                        'level',
                    ];
                }
            PHP);
        }

        if (!Schema::hasTable('buildings')) {
            Schema::create('buildings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
                $table->unsignedTinyInteger('slot_number');
                $table->unsignedSmallInteger('building_type')->nullable();
                $table->unsignedTinyInteger('level')->default(0);
                $table->timestamps();
                $table->unique(['village_id', 'slot_number']);
            });
        }

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
            'slot_number' => 1,
            'building_type' => 19,
            'level' => 3,
        ]);

        $this->seedLegacyBuilding($village->id, 19, 3, 1);

        $this->assertTrue($this->service->canTrain($village->fresh(), 1, 5, 'barracks'));
    }

    public function test_calculate_training_time_accounts_for_building_level(): void
    {
        $village = Village::factory()->create();

        $building = VillageBuilding::factory()->create([
            'village_id' => $village->id,
            'slot_number' => 1,
            'building_type' => 19,
            'level' => 1,
        ]);

        $this->seedLegacyBuilding($village->id, 19, 1, $building->slot_number ?? 1);

        $calculationLevelOne = $this->service->calculateTrainingTime($village->fresh(), 1, 10, 'barracks');

        $building->update(['level' => 10]);
        $this->seedLegacyBuilding($village->id, 19, 10, $building->slot_number ?? 1);
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
            'slot_number' => 1,
            'building_type' => 19,
            'level' => 5,
        ]);

        $this->seedLegacyBuilding($village->id, 19, 5, 1);

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

    public function test_calculate_training_time_applies_context_modifiers(): void
    {
        $village = Village::factory()->create();

        VillageBuilding::factory()->create([
            'village_id' => $village->id,
            'slot_number' => 1,
            'building_type' => 19,
            'level' => 5,
        ]);

        $this->seedLegacyBuilding($village->id, 19, 5, 1);

        $calculation = $this->service->calculateTrainingTime(
            $village->fresh(),
            unitTypeId: 1,
            quantity: 10,
            trainingBuilding: 'barracks',
            context: [
                'base_per_unit' => 60,
                'percentage_reduction' => 20,
                'speed_multiplier' => 0.5,
            ],
        );

        $this->assertSame(160, $calculation['total_seconds']);
        $this->assertSame(16, $calculation['per_unit_seconds']);
        $this->assertSame(60, $calculation['base_per_unit_seconds']);
        $this->assertSame(5, $calculation['building_level']);
        $this->assertEqualsWithDelta(0.6561, $calculation['modifiers']['building'], 0.0001);
        $this->assertSame(20.0, $calculation['modifiers']['percentage_reduction']);
        $this->assertSame(0.5, $calculation['modifiers']['speed_multiplier']);
    }

    protected function seedLegacyBuilding(int $villageId, int $type, int $level, int $slot): void
    {
        DB::table('buildings')->updateOrInsert(
            ['village_id' => $villageId, 'slot_number' => $slot],
            [
                'building_type' => $type,
                'level' => $level,
                'updated_at' => Carbon::now(),
                'created_at' => Carbon::now(),
            ]
        );
    }
}
