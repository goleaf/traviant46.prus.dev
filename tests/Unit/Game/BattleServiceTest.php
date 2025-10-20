<?php

namespace Tests\Unit\Game;

use App\Services\BattleService;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(BattleService::class)]
class BattleServiceTest extends TestCase
{
    public function test_simulate_battle_calculates_losses_and_loot(): void
    {
        $service = new BattleService();

        $attacker = [
            'units' => [
                'swordsman' => ['count' => 100, 'attack' => 40, 'defense' => 20, 'carry' => 50],
                'archer' => ['count' => 60, 'attack' => 30, 'defense' => 15, 'carry' => 30],
            ],
        ];

        $defender = [
            'units' => [
                'spearman' => ['count' => 50, 'defense' => 60],
                'pikeman' => ['count' => 40, 'defense' => 50],
            ],
            'resources' => [
                'wood' => 500,
                'clay' => 400,
                'iron' => 200,
                'crop' => 100,
            ],
        ];

        $result = $service->simulateBattle($attacker, $defender);

        $this->assertSame('attacker', $result['winner']);
        $this->assertSame([
            'wood' => 500,
            'clay' => 400,
            'iron' => 40,
            'crop' => 0,
        ], $result['loot']['resources']);
        $this->assertSame([
            'wood' => 0,
            'clay' => 0,
            'iron' => 160,
            'crop' => 100,
        ], $result['loot']['defenderRemaining']);
        $this->assertSame(940, $result['loot']['capacity']['total']);
        $this->assertSame(940, $result['loot']['capacity']['used']);
        $this->assertSame(0, $result['loot']['capacity']['remaining']);
        $this->assertEqualsWithDelta(5800.0, $result['strength']['attacker'], 0.001);
        $this->assertEqualsWithDelta(5000.0, $result['strength']['defender'], 0.001);
        $this->assertSame([
            'swordsman' => 86,
            'archer' => 52,
        ], $result['casualties']['attacker']['losses']);
        $this->assertSame([
            'swordsman' => 14,
            'archer' => 8,
        ], $result['casualties']['attacker']['survivors']);
        $this->assertSame([
            'spearman' => 50,
            'pikeman' => 40,
        ], $result['casualties']['defender']['losses']);
        $this->assertSame([
            'spearman' => 0,
            'pikeman' => 0,
        ], $result['casualties']['defender']['survivors']);
    }

    public function test_calculate_casualties_handles_draw_when_no_power(): void
    {
        $service = new BattleService();

        $result = $service->calculateCasualties(
            ['soldier' => ['count' => 0]],
            ['guard' => ['count' => 0]]
        );

        $this->assertSame('draw', $result['winner']);
        $this->assertEquals(0.0, $result['attacker']['power']);
        $this->assertEquals(0.0, $result['defender']['power']);
        $this->assertEquals(0.0, $result['attacker']['lossRate']);
        $this->assertEquals(0.0, $result['defender']['lossRate']);
        $this->assertSame(['soldier' => 0], $result['attacker']['losses']);
        $this->assertSame(['guard' => 0], $result['defender']['losses']);
    }
}
