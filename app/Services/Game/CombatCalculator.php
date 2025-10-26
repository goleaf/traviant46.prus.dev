<?php

declare(strict_types=1);

namespace App\Services\Game;

use InvalidArgumentException;

class CombatCalculator
{
    private const BASE_VILLAGE_DEFENSE = 10.0;

    private const HERO_OFFENSE = 100.0;

    private const HERO_DEFENSE_INFANTRY = 100.0;

    private const HERO_DEFENSE_CAVALRY = 100.0;

    /**
     * @var array<int, array<int, int>>
     */
    private array $offenseTables = [
        1 => [1 => 40, 30, 70, 0, 120, 180, 60, 75, 50, 0],
        2 => [1 => 40, 10, 60, 0, 55, 150, 65, 50, 40, 10],
        3 => [1 => 15, 65, 0, 90, 45, 140, 50, 70, 40, 0],
        4 => [1 => 10, 20, 60, 80, 50, 100, 250, 450, 200, 600],
        5 => [1 => 20, 65, 100, 0, 155, 170, 250, 60, 80, 30],
        6 => [1 => 10, 30, 65, 0, 50, 110, 55, 65, 40, 0],
        7 => [1 => 35, 50, 0, 120, 115, 180, 65, 45, 40, 0],
    ];

    /**
     * @var array<int, array<int, int>>
     */
    private array $defenseInfantryTables = [
        1 => [1 => 35, 65, 40, 20, 65, 80, 30, 60, 40, 80],
        2 => [1 => 20, 35, 30, 10, 100, 50, 30, 60, 60, 80],
        3 => [1 => 40, 35, 20, 25, 115, 50, 30, 45, 50, 80],
        4 => [1 => 25, 35, 40, 66, 70, 80, 140, 380, 170, 440],
        5 => [1 => 35, 30, 90, 10, 80, 140, 120, 45, 50, 40],
        6 => [1 => 30, 55, 50, 20, 110, 120, 30, 55, 50, 80],
        7 => [1 => 40, 30, 20, 30, 80, 60, 30, 55, 50, 80],
    ];

    /**
     * @var array<int, array<int, int>>
     */
    private array $defenseCavalryTables = [
        1 => [1 => 50, 35, 25, 10, 50, 105, 75, 10, 30, 80],
        2 => [1 => 5, 60, 30, 5, 40, 75, 80, 10, 40, 80],
        3 => [1 => 50, 20, 10, 40, 55, 165, 105, 10, 50, 80],
        4 => [1 => 20, 40, 60, 50, 33, 70, 200, 240, 250, 520],
        5 => [1 => 50, 10, 75, 0, 50, 80, 150, 10, 50, 40],
        6 => [1 => 20, 40, 20, 10, 50, 150, 95, 10, 50, 80],
        7 => [1 => 30, 10, 10, 15, 70, 40, 90, 10, 50, 80],
    ];

    /**
     * @var array<int, array<int, int>>
     */
    private array $upkeepTables = [
        1 => [1 => 1, 1, 1, 2, 3, 4, 3, 6, 5, 1, 6],
        2 => [1 => 1, 1, 1, 1, 2, 3, 3, 6, 4, 1, 6],
        3 => [1 => 1, 1, 2, 2, 2, 3, 3, 6, 4, 1, 6],
        4 => [1 => 1, 1, 1, 1, 2, 2, 3, 3, 3, 5, 0],
        5 => [1 => 1, 1, 1, 1, 2, 3, 6, 5, 0, 0, 6],
        6 => [1 => 1, 1, 1, 2, 2, 3, 3, 6, 4, 1, 6],
        7 => [1 => 1, 1, 2, 2, 2, 3, 3, 6, 4, 1, 6],
    ];

    /**
     * @var array<int, array<int, int>>
     */
    private array $cavalryUnits = [
        1 => [4 => 1, 5 => 1, 6 => 1],
        2 => [4 => 1, 5 => 1, 6 => 1],
        3 => [3 => 1, 4 => 1, 5 => 1, 6 => 1],
        4 => [],
        5 => [5 => 1, 6 => 1],
        6 => [4 => 1, 5 => 1, 6 => 1],
        7 => [4 => 1, 5 => 1, 6 => 1],
    ];

    /**
     * @var array<int, float>
     */
    private array $wallBase = [
        1 => 1.03,
        2 => 1.02,
        3 => 1.025,
        4 => 1.0,
        5 => 1.03,
        6 => 1.025,
        7 => 1.015,
    ];

    /**
     * @var array<int, int>
     */
    private array $wallExtra = [
        1 => 10,
        2 => 6,
        3 => 8,
        4 => 0,
        5 => 10,
        6 => 8,
        7 => 6,
    ];

    /**
     * @var array<int, int>
     */
    private array $wallDurability = [
        1 => 1,
        2 => 5,
        3 => 2,
        4 => 1,
        5 => 1,
        6 => 5,
        7 => 1,
    ];

    /**
     * @param array<string, int> $units
     * @return array{total: float, infantry: float, cavalry: float, by_slot: array<string, float>}
     */
    public function offensiveProfile(array $units, int $tribe): array
    {
        $table = $this->offenseTables[$tribe] ?? $this->offenseTables[1];
        $cavalryMap = $this->cavalryUnits[$tribe] ?? [];

        $total = 0.0;
        $infantry = 0.0;
        $cavalry = 0.0;
        $bySlot = [];

        foreach ($units as $slot => $count) {
            if ($count <= 0) {
                continue;
            }

            $index = $this->slotIndex($slot);
            if ($index === null) {
                continue;
            }

            if ($index === 11) {
                $power = self::HERO_OFFENSE * $count;
                $bySlot[$slot] = $power;
                $total += $power;
                $infantry += $power;

                continue;
            }

            $base = $table[$index] ?? 0;
            $power = $base * $count;
            $bySlot[$slot] = $power;
            $total += $power;

            if (isset($cavalryMap[$index])) {
                $cavalry += $power;
            } else {
                $infantry += $power;
            }
        }

        return [
            'total' => $total,
            'infantry' => $infantry,
            'cavalry' => $cavalry,
            'by_slot' => $bySlot,
        ];
    }

    /**
     * @param array<string, int> $units
     * @return array{total: float, infantry: float, cavalry: float}
     */
    public function defensiveProfile(array $units, int $tribe): array
    {
        $infantryTable = $this->defenseInfantryTables[$tribe] ?? $this->defenseInfantryTables[1];
        $cavalryTable = $this->defenseCavalryTables[$tribe] ?? $this->defenseCavalryTables[1];

        $infantry = 0.0;
        $cavalry = 0.0;

        foreach ($units as $slot => $count) {
            if ($count <= 0) {
                continue;
            }

            $index = $this->slotIndex($slot);
            if ($index === null) {
                continue;
            }

            if ($index === 11) {
                $infantry += self::HERO_DEFENSE_INFANTRY * $count;
                $cavalry += self::HERO_DEFENSE_CAVALRY * $count;

                continue;
            }

            $infantry += ($infantryTable[$index] ?? 0) * $count;
            $cavalry += ($cavalryTable[$index] ?? 0) * $count;
        }

        return [
            'total' => $infantry + $cavalry,
            'infantry' => $infantry,
            'cavalry' => $cavalry,
        ];
    }

    /**
     * @param array{infantry: float, cavalry: float} $defense
     * @param array{total: float, infantry: float, cavalry: float} $attackerProfile
     */
    public function effectiveDefense(
        array $defense,
        array $attackerProfile,
        int $wallLevel,
        ?int $defenderTribe,
        float $wallBonus = 0.0,
        float $moraleModifier = 1.0
    ): float {
        $attackTotal = max(1.0, $attackerProfile['total']);
        $infantryShare = $attackerProfile['infantry'] / $attackTotal;
        $cavalryShare = $attackerProfile['cavalry'] / $attackTotal;

        $baseDefense = ($defense['infantry'] * $infantryShare) + ($defense['cavalry'] * $cavalryShare);
        $baseDefense += self::BASE_VILLAGE_DEFENSE;

        if ($defenderTribe !== null) {
            $extra = ($this->wallExtra[$defenderTribe] ?? 0) * max(0, $wallLevel);
            $baseDefense += $extra;
            $multiplier = pow($this->wallBase[$defenderTribe] ?? 1.0, max(0, $wallLevel));
            $baseDefense *= $multiplier;
        }

        $baseDefense += max(0.0, $wallBonus);
        $modifier = max(0.0, $moraleModifier);

        return $baseDefense * $modifier;
    }

    /**
     * @return array{attacker: float, defender: float}
     */
    public function casualtyRates(float $attackPower, float $defensePower, bool $isRaid): array
    {
        $attackPower = max(0.0, $attackPower);
        $defensePower = max(0.0, $defensePower);

        if ($attackPower <= 0.0 && $defensePower <= 0.0) {
            return ['attacker' => 0.0, 'defender' => 0.0];
        }

        if ($attackPower <= 0.0) {
            return ['attacker' => 1.0, 'defender' => 0.0];
        }

        if ($defensePower <= 0.0) {
            return ['attacker' => 0.0, 'defender' => 1.0];
        }

        $exponent = $isRaid ? 1.2 : 1.5;

        if ($attackPower >= $defensePower) {
            $lossRatio = pow($defensePower / $attackPower, $exponent);

            return [
                'attacker' => min(1.0, $lossRatio),
                'defender' => 1.0,
            ];
        }

        $lossRatio = pow($attackPower / $defensePower, $exponent);

        return [
            'attacker' => 1.0,
            'defender' => min(1.0, $lossRatio),
        ];
    }

    /**
     * @param array<string, int> $units
     */
    public function upkeep(array $units, int $tribe): int
    {
        $table = $this->upkeepTables[$tribe] ?? $this->upkeepTables[1];
        $total = 0;

        foreach ($units as $slot => $count) {
            if ($count <= 0) {
                continue;
            }

            $index = $this->slotIndex($slot);
            if ($index === null) {
                continue;
            }

            $total += ($table[$index] ?? 0) * $count;
        }

        return $total;
    }

    /**
     * @return array{damage: int, resulting_level: int}
     */
    public function applyRamDamage(int $currentWallLevel, ?int $defenderTribe, int $survivingRams): array
    {
        if ($currentWallLevel <= 0 || $survivingRams <= 0) {
            return ['damage' => 0, 'resulting_level' => $currentWallLevel];
        }

        $tribe = $defenderTribe ?? 1;
        $durability = max(1, $this->wallDurability[$tribe] ?? 1);

        $damage = (int) floor($survivingRams / (5 * $durability));

        if ($damage <= 0) {
            return ['damage' => 0, 'resulting_level' => $currentWallLevel];
        }

        $newLevel = max(0, $currentWallLevel - $damage);

        return ['damage' => $currentWallLevel - $newLevel, 'resulting_level' => $newLevel];
    }

    public function catapultDamage(int $survivingCatapults): int
    {
        if ($survivingCatapults <= 0) {
            return 0;
        }

        return max(1, (int) floor($survivingCatapults / 5));
    }

    public function moraleModifier(?int $attackerPopulation, ?int $defenderPopulation): float
    {
        if (
            $attackerPopulation === null
            || $attackerPopulation <= 0
            || $defenderPopulation === null
            || $defenderPopulation <= 0
        ) {
            return 1.0;
        }

        $ratio = $defenderPopulation / max(1, $attackerPopulation);
        $ratio = max(0.25, min(4.0, $ratio));
        $modifier = pow($ratio, -0.3);

        return max(0.75, min(1.25, $modifier));
    }

    public function randomModifier(int $seed): float
    {
        $hash = crc32((string) $seed);
        $normalized = ($hash % 1000) / 1000;

        return 0.9 + (0.2 * $normalized);
    }

    private function slotIndex(string $slot): ?int
    {
        if (! str_starts_with($slot, 'u')) {
            return null;
        }

        $value = substr($slot, 1);

        if ($value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException(sprintf('Unexpected unit slot [%s].', $slot));
        }

        return (int) $value;
    }
}
