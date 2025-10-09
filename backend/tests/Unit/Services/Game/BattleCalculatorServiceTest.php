<?php

namespace Tests\Unit\Services\Game;

use App\Services\Game\BattleCalculatorService;
use App\ValueObjects\Game\Combat\ArmyComposition;
use PHPUnit\Framework\TestCase;

class BattleCalculatorServiceTest extends TestCase
{
    private BattleCalculatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BattleCalculatorService();
    }

    public function test_it_returns_unit_stats(): void
    {
        $stats = $this->service->getUnitStats(1, 1);

        $this->assertSame(40, $stats->offense);
        $this->assertSame(35, $stats->infantryDefense);
        $this->assertSame(50, $stats->cavalryDefense);
        $this->assertSame(1, $stats->upkeep);
        $this->assertFalse($stats->isCavalry);
    }

    public function test_calculate_offense_and_defense(): void
    {
        $army = new ArmyComposition(1, [
            1 => 50,
            6 => 10,
        ]);

        $this->assertSame(3800, $this->service->calculateOffense($army));

        $defense = $this->service->calculateDefense(new ArmyComposition(2, [
            1 => 25,
            5 => 10,
        ]));

        $this->assertSame(20 * 25 + 100 * 10 + 10, $defense['infantry']);
        $this->assertSame(5 * 25 + 40 * 10 + 10, $defense['cavalry']);
    }

    public function test_resolve_battle_accounts_for_cavalry_share(): void
    {
        $attacker = new ArmyComposition(1, [
            1 => 50,
            6 => 10,
        ]);
        $defender = new ArmyComposition(1, [
            2 => 30,
            5 => 5,
        ]);

        $report = $this->service->resolveBattle($attacker, $defender);

        $this->assertGreaterThan(0, $report->attackerStrength);
        $this->assertGreaterThan(0, $report->defenderStrength);
        $this->assertGreaterThan(0, $report->cavalryShare);
        $this->assertTrue($report->strengthRatio() > 0);
    }
}
