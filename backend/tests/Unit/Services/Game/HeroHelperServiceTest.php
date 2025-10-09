<?php

namespace Tests\Unit\Services\Game;

use App\Services\Game\HeroHelperService;
use App\Support\Game\Hero\InMemoryHeroItemRepository;
use App\ValueObjects\Game\Hero\HeroItem;
use PHPUnit\Framework\TestCase;

class HeroHelperServiceTest extends TestCase
{
    private HeroHelperService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = new InMemoryHeroItemRepository([
            new HeroItem(1, 20, 4, ['hero_power' => 15]),
            new HeroItem(2, 77, 3, ['hero_power' => 5, 'raid' => 25]),
            new HeroItem(3, 90, 2, ['hero_power' => 10, 'reg' => 8]),
            new HeroItem(4, 4, 1, ['reg' => 4]),
            new HeroItem(5, 94, 5, ['reg' => 3]),
            new HeroItem(6, 84, 2, ['reg' => 6, 'resist' => 6]),
            new HeroItem(7, 103, 6, ['speed_horse' => 5]),
            new HeroItem(8, 100, 5, ['hero_cav_speed' => 3]),
            new HeroItem(9, 13, 1, ['inf' => 10, 'cav' => 15]),
            new HeroItem(10, 73, 3, ['raid' => 25, 'hero_power' => 7]),
            new HeroItem(11, 112, 7, ['num' => 5, 'revive' => 0.3]),
            new HeroItem(12, 114, 9, ['num' => 10]),
        ]);

        $this->service = new HeroHelperService($repository, 3, 1.2);
    }

    public function test_calculate_total_power_includes_item_bonuses(): void
    {
        $total = $this->service->calculateTotalPower(1, 3, 1, 2, 3);
        $this->assertSame(430.0, $total);
    }

    public function test_calculate_total_health_and_resistance(): void
    {
        $this->assertSame(33.0, $this->service->calculateTotalHealth(4, 6, 5));
        $this->assertSame(6.0, $this->service->calculateResist(6));
    }

    public function test_speed_calculations_include_items(): void
    {
        $speed = $this->service->calculateTotalSpeed(3, 7, 8);
        $this->assertEqualsWithDelta(22.4, $speed, 0.001);
    }

    public function test_bandages_cages_and_rob_points(): void
    {
        $bandages = $this->service->getBandages(11);
        $this->assertSame(['num' => 5, 'eff' => 0.3], $bandages);
        $this->assertSame(10, $this->service->getCages(12));
        $this->assertSame(25.0, $this->service->calculateRobPoints(10));
    }

    public function test_train_effect_uses_helmet_bonus(): void
    {
        $effect = $this->service->calculateTrainEffect(9);
        $this->assertSame([10.0, 15.0], $effect);
    }
}
