<?php

namespace Tests\Unit\Game;

use App\Jobs\ProcessResourceTick;
use App\Jobs\ProcessStarvation;
use App\Models\Game\Village;
use App\Models\Game\VillageResource;
use App\Models\Game\VillageUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ResourceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_tick_accumulates_until_capacity(): void
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        $village = Village::create([
            'name' => 'Test Village',
            'population' => 0,
            'loyalty' => 100,
            'x_coordinate' => 0,
            'y_coordinate' => 0,
        ]);

        $resource = VillageResource::create([
            'village_id' => $village->getKey(),
            'resource_type' => VillageResource::TYPE_WOOD,
            'current_stock' => 0,
            'storage_capacity' => 50,
            'production_per_hour' => 60,
            'last_calculated_at' => Carbon::now()->subMinutes(45),
        ]);

        (new ProcessResourceTick())->handle();

        $resource->refresh();

        $this->assertEquals(45.0, (float) $resource->current_stock);
        $this->assertEqualsWithDelta(Carbon::now()->timestamp, $resource->last_calculated_at?->timestamp ?? 0, 1);

        Carbon::setTestNow('2025-01-01 01:00:00');
        (new ProcessResourceTick())->handle();

        $resource->refresh();
        $this->assertEquals(50.0, (float) $resource->current_stock, 'Resource stock should be clamped to capacity.');
    }

    public function test_starvation_reduces_troop_quantities(): void
    {
        Config::set('game.starvation.enabled', true);
        Carbon::setTestNow('2025-01-01 00:00:00');

        $village = Village::create([
            'name' => 'Hungry Village',
            'population' => 0,
            'loyalty' => 100,
            'x_coordinate' => 10,
            'y_coordinate' => 10,
        ]);

        $crop = VillageResource::create([
            'village_id' => $village->getKey(),
            'resource_type' => VillageResource::TYPE_CROP,
            'current_stock' => -25,
            'storage_capacity' => 100,
            'production_per_hour' => 0,
            'last_calculated_at' => Carbon::now()->subHour(),
        ]);

        VillageUnit::create([
            'village_id' => $village->getKey(),
            'unit_type_id' => 1,
            'quantity' => 20,
        ]);

        (new ProcessStarvation())->handle();

        $crop->refresh();
        $unit = VillageUnit::where('village_id', $village->getKey())->first();

        $this->assertSame(0.0, (float) $crop->current_stock);
        $this->assertSame(0, $unit?->quantity ?? 0);
    }
}
