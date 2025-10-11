<?php

namespace Tests\Unit\Game;

use App\Services\ResourceService;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(ResourceService::class)]
class ResourceServiceTest extends TestCase
{
    public function test_calculate_production_handles_mixed_modifiers(): void
    {
        $service = new ResourceService();

        $production = $service->calculateProduction(
            ['wood' => 100, 'clay' => 90, 'iron' => 80, 'crop' => 120],
            [
                'percent' => [
                    'all' => 20,
                    'clay' => 5,
                    'crop' => 10,
                ],
                'flat' => [
                    'wood' => 30,
                    '*' => 10,
                ],
                'upkeep' => [
                    'crop' => 50,
                    'all' => 5,
                ],
                'options' => [
                    'precision' => 2,
                    'allow_negative_crop' => true,
                    'global_multiplier' => 1.1,
                ],
            ]
        );

        $this->assertEqualsWithDelta(184.8, $production['wood'], 0.0001);
        $this->assertEqualsWithDelta(137.5, $production['clay'], 0.0001);
        $this->assertEqualsWithDelta(118.8, $production['iron'], 0.0001);
        $this->assertEqualsWithDelta(125.4, $production['crop'], 0.0001);
    }

    public function test_update_resources_applies_elapsed_time_and_storage_clamps(): void
    {
        $service = new ResourceService();

        $result = $service->updateResources(
            ['wood' => 950, 'clay' => 950, 'iron' => 950, 'crop' => 900],
            ['wood' => 100, 'clay' => 80, 'iron' => 60, 'crop' => 50],
            3600,
            [
                'precision' => 0,
                'storage' => [
                    'wood' => 1000,
                    'clay' => 1000,
                    'iron' => 1000,
                    'crop' => 950,
                ],
            ]
        );

        $this->assertSame([
            'wood' => 1000.0,
            'clay' => 1000.0,
            'iron' => 1000.0,
            'crop' => 950.0,
        ], $result['resources']);
        $this->assertSame([
            'wood' => 50.0,
            'clay' => 30.0,
            'iron' => 10.0,
            'crop' => 0.0,
        ], $result['overflow']);
        $this->assertTrue($result['hadOverflow']);
        $this->assertSame(3600.0, $result['elapsed_seconds']);
    }
}
