<?php

declare(strict_types=1);

namespace Tests\Unit\Game;

use App\Enums\Game\VillageBuildingUpgradeStatus;
use App\Models\Game\Village;
use App\Models\Game\VillageBuilding;
use App\Models\Game\VillageBuildingUpgrade;
use App\Services\Game\BuildingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(BuildingService::class)]
class BuildingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(VillageBuilding::class)) {
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

        Village::resolveRelationUsing('buildingUpgrades', function (Village $village) {
            return $village->hasMany(VillageBuildingUpgrade::class);
        });

        Village::resolveRelationUsing('buildings', function (Village $village) {
            return $village->hasMany(VillageBuilding::class);
        });
    }

    public function test_can_upgrade_respects_capital_limits_and_pending_queue(): void
    {
        $village = Village::factory()->create(['is_capital' => false]);

        $building = VillageBuilding::factory()->create([
            'village_id' => $village->id,
            'slot_number' => 5,
            'building_type' => 1,
            'level' => 5,
        ]);

        $service = new BuildingService;

        $this->assertTrue($service->canUpgrade($village->fresh(), $building->fresh(), 3));
        $this->assertFalse($service->canUpgrade($village->fresh(), $building->fresh(), 6));

        VillageBuildingUpgrade::create([
            'village_id' => $village->id,
            'village_building_id' => $building->id,
            'slot_number' => $building->slot_number,
            'building_type' => $building->building_type,
            'current_level' => $building->level,
            'target_level' => 8,
            'queue_position' => 0,
            'status' => VillageBuildingUpgradeStatus::Pending,
            'metadata' => [],
            'queued_at' => Carbon::now(),
            'starts_at' => Carbon::now(),
            'completes_at' => Carbon::now()->addHour(),
        ]);

        $this->assertTrue($service->canUpgrade($village->fresh(), $building->fresh(), 2));
        $this->assertFalse($service->canUpgrade($village->fresh(), $building->fresh(), 3));
    }

    public function test_upgrade_creates_segmented_queue(): void
    {
        Carbon::setTestNow('2025-05-05 12:00:00');

        $village = Village::factory()->create(['is_capital' => true]);

        $building = VillageBuilding::factory()->create([
            'village_id' => $village->id,
            'slot_number' => 7,
            'building_type' => 19,
            'level' => 1,
        ]);

        $service = new BuildingService(gameSpeed: 2.0);

        $upgrade = $service->upgrade(
            $village->fresh(),
            $building->fresh(),
            levels: 2,
            context: [
                'main_building_level' => 5,
            ],
        );

        $this->assertSame(0, $upgrade->queue_position);
        $this->assertSame(3, $upgrade->target_level);
        $this->assertSame(VillageBuildingUpgradeStatus::Pending, $upgrade->status);
        $this->assertCount(2, $upgrade->metadata['segments']);
        $this->assertSame(2, $upgrade->metadata['segments'][0]['level']);
        $this->assertSame(3, $upgrade->metadata['segments'][1]['level']);
        $this->assertTrue($upgrade->starts_at->equalTo(Carbon::now()));
        $this->assertTrue($upgrade->completes_at->greaterThan($upgrade->starts_at));

        Carbon::setTestNow();
    }

    public function test_calculate_build_time_scales_with_game_speed(): void
    {
        $slow = new BuildingService(gameSpeed: 1.0);
        $fast = new BuildingService(gameSpeed: 3.0);

        $slowTime = $slow->calculateBuildTime(1, 5, 0);
        $fastTime = $fast->calculateBuildTime(1, 5, 0);

        $this->assertGreaterThan($fastTime, $slowTime);

        $withMainBuilding = $slow->calculateBuildTime(1, 5, 10);
        $this->assertLessThan($slowTime, $withMainBuilding);
    }
}
