<?php

namespace App\Services\Game;

use App\ValueObjects\Game\Combat\ArmyComposition;
use App\ValueObjects\Game\Combat\BattleReport;
use App\ValueObjects\Game\Combat\UnitStats;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class BattleCalculatorService
{
    private const BASE_VILLAGE_DEF = 10;

    /**
     * Raw tribe configuration ported from the legacy battle service.
     * @var array<int,array<int,int>>
     */
    private array $offense = [
        1 => [1 => 40, 30, 70, 0, 120, 180, 60, 75, 50, 0],
        2 => [1 => 40, 10, 60, 0, 55, 150, 65, 50, 40, 10],
        3 => [1 => 15, 65, 0, 90, 45, 140, 50, 70, 40, 0],
        4 => [1 => 10, 20, 60, 80, 50, 100, 250, 450, 200, 600],
        5 => [1 => 20, 65, 100, 0, 155, 170, 250, 60, 80, 30],
        6 => [1 => 10, 30, 65, 0, 50, 110, 55, 65, 40, 0],
        7 => [1 => 35, 50, 0, 120, 115, 180, 65, 45, 40, 0],
    ];

    /**
     * @var array<int,array<int,int>>
     */
    private array $defInfantry = [
        1 => [1 => 35, 65, 40, 20, 65, 80, 30, 60, 40, 80],
        2 => [1 => 20, 35, 30, 10, 100, 50, 30, 60, 60, 80],
        3 => [1 => 40, 35, 20, 25, 115, 50, 30, 45, 50, 80],
        4 => [1 => 25, 35, 40, 66, 70, 80, 140, 380, 170, 440],
        5 => [1 => 35, 30, 90, 10, 80, 140, 120, 45, 50, 40],
        6 => [1 => 30, 55, 50, 20, 110, 120, 30, 55, 50, 80],
        7 => [1 => 40, 30, 20, 30, 80, 60, 30, 55, 50, 80],
    ];

    /**
     * @var array<int,array<int,int>>
     */
    private array $defCavalry = [
        1 => [1 => 50, 35, 25, 10, 50, 105, 75, 10, 30, 80],
        2 => [1 => 5, 60, 30, 5, 40, 75, 80, 10, 40, 80],
        3 => [1 => 50, 20, 10, 40, 55, 165, 105, 10, 50, 80],
        4 => [1 => 20, 40, 60, 50, 33, 70, 200, 240, 250, 520],
        5 => [1 => 50, 10, 75, 0, 50, 80, 150, 10, 50, 40],
        6 => [1 => 20, 40, 20, 10, 50, 150, 95, 10, 50, 80],
        7 => [1 => 30, 10, 10, 15, 70, 40, 90, 10, 50, 80],
    ];

    /**
     * @var array<int,array<int,int>>
     */
    private array $upkeep = [
        1 => [1 => 1, 1, 1, 2, 3, 4, 3, 6, 5, 1, 6],
        2 => [1 => 1, 1, 1, 1, 2, 3, 3, 6, 4, 1, 6],
        3 => [1 => 1, 1, 2, 2, 2, 3, 3, 6, 4, 1, 6],
        4 => [1 => 1, 1, 1, 1, 2, 2, 3, 3, 3, 5, 0],
        5 => [1 => 1, 1, 1, 1, 2, 3, 6, 5, 0, 0, 6],
        6 => [1 => 1, 1, 1, 2, 2, 3, 3, 6, 4, 1, 6],
        7 => [1 => 1, 1, 2, 2, 2, 3, 3, 6, 4, 1, 6],
    ];

    /**
     * @var array<int,array<int,bool>>
     */
    private array $cavalrySlots = [
        1 => [4 => true, 5 => true, 6 => true],
        2 => [4 => true, 5 => true, 6 => true],
        3 => [3 => true, 4 => true, 5 => true, 6 => true],
        4 => [],
        5 => [5 => true, 6 => true],
        6 => [4 => true, 5 => true, 6 => true],
        7 => [4 => true, 5 => true, 6 => true],
    ];

    public function getUnitStats(int $tribe, int $slot): UnitStats
    {
        if ($tribe < 1 || $tribe > 7 || $slot < 1 || $slot > 11) {
            throw new InvalidArgumentException('Unsupported tribe or unit slot.');
        }

        $offense = Arr::get($this->offense, "$tribe.$slot", 0);
        $defInf = Arr::get($this->defInfantry, "$tribe.$slot", 0);
        $defCav = Arr::get($this->defCavalry, "$tribe.$slot", 0);
        $upkeep = Arr::get($this->upkeep, "$tribe.$slot", 0);
        $isCavalry = (bool) Arr::get($this->cavalrySlots, "$tribe.$slot", false);

        return new UnitStats($offense, $defInf, $defCav, $upkeep, $isCavalry);
    }

    public function calculateOffense(ArmyComposition $army): int
    {
        $total = 0;
        foreach ($army->units as $slot => $amount) {
            $stats = $this->getUnitStats($army->tribe, $slot);
            $total += $stats->offense * $amount;
        }

        return max(0, (int) round($total));
    }

    /**
     * @return array{infantry:int,cavalry:int}
     */
    public function calculateDefense(ArmyComposition $army): array
    {
        $inf = self::BASE_VILLAGE_DEF;
        $cav = self::BASE_VILLAGE_DEF;

        foreach ($army->units as $slot => $amount) {
            $stats = $this->getUnitStats($army->tribe, $slot);
            $inf += $stats->infantryDefense * $amount;
            $cav += $stats->cavalryDefense * $amount;
        }

        return [
            'infantry' => (int) round($inf),
            'cavalry' => (int) round($cav),
        ];
    }

    public function calculateUpkeep(ArmyComposition $army): int
    {
        $total = 0;
        foreach ($army->units as $slot => $amount) {
            $stats = $this->getUnitStats($army->tribe, $slot);
            $total += $stats->upkeep * $amount;
        }

        return (int) round($total);
    }

    public function resolveBattle(ArmyComposition $attacker, ArmyComposition $defender): BattleReport
    {
        $attackPower = $this->calculateOffense($attacker);
        $defense = $this->calculateDefense($defender);

        $cavalryAttack = 0;
        foreach ($attacker->units as $slot => $amount) {
            $stats = $this->getUnitStats($attacker->tribe, $slot);
            if ($stats->isCavalry) {
                $cavalryAttack += $stats->offense * $amount;
            }
        }

        $cavalryShare = $attackPower === 0 ? 0.0 : $cavalryAttack / $attackPower;
        $cavalryShare = min(1, max(0, $cavalryShare));

        $effectiveDefense = (1 - $cavalryShare) * $defense['infantry'] + $cavalryShare * $defense['cavalry'];

        return new BattleReport(
            (int) round($attackPower),
            (int) round($effectiveDefense),
            $cavalryShare
        );
    }
}
