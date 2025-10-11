<?php

namespace App\Services;

class BattleService
{
    /**
     * Simulate a battle between the provided attacker and defender payloads.
     *
     * The method is intentionally data-shape agnostic so it can work with both the
     * legacy Travian arrays and new typed DTO-like payloads. At minimum the
     * provided arrays should contain a list of units. The method will calculate
     * the strength of each side, determine the losses, and finally distribute the
     * loot that the surviving attackers are able to carry away.
     *
     * Expected array structure (flexible):
     * - `['units' => [<unitKey> => ['count' => 10, 'attack' => 40, 'defense' => 20, 'carry' => 50]]]`
     * - or simply `[<unitKey> => ['count' => 10, ...]]`
     *
     * Optional fields used when present:
     * - `resources` on the defender for loot distribution.
     * - `carry`/`capacity`/`load` on units to compute carrying capacity.
     */
    public function simulateBattle(array $attacker, array $defender): array
    {
        $attackerUnits = $this->extractUnits($attacker);
        $defenderUnits = $this->extractUnits($defender);

        $strength = [
            'attacker' => $this->calculateArmyStrength($attackerUnits, 'attack'),
            'defender' => $this->calculateArmyStrength($defenderUnits, 'defense'),
        ];

        $casualties = $this->calculateCasualties($attackerUnits, $defenderUnits);

        $loot = $this->distributeLoot(
            ['units' => $attackerUnits] + $attacker,
            ['resources' => $defender['resources'] ?? []] + $defender,
            $casualties
        );

        return [
            'winner' => $casualties['winner'],
            'strength' => $strength,
            'casualties' => $casualties,
            'loot' => $loot,
        ];
    }

    /**
     * Calculate casualties for attacker and defender units.
     *
     * The calculation uses the total army strength of each side to derive a
     * percentage of losses. When one side has zero effective strength all units
     * on that side are considered lost.
     */
    public function calculateCasualties(array $attackerUnits, array $defenderUnits): array
    {
        $attackerUnits = $this->extractUnits($attackerUnits);
        $defenderUnits = $this->extractUnits($defenderUnits);

        $attackerPower = $this->calculateArmyStrength($attackerUnits, 'attack');
        $defenderPower = $this->calculateArmyStrength($defenderUnits, 'defense');

        if ($attackerPower <= 0 && $defenderPower <= 0) {
            return [
                'winner' => 'draw',
                'attacker' => [
                    'power' => 0,
                    'lossRate' => 0.0,
                    'losses' => $this->zeroLosses($attackerUnits),
                    'survivors' => $this->zeroLosses($attackerUnits, false),
                ],
                'defender' => [
                    'power' => 0,
                    'lossRate' => 0.0,
                    'losses' => $this->zeroLosses($defenderUnits),
                    'survivors' => $this->zeroLosses($defenderUnits, false),
                ],
            ];
        }

        $attackerLossRate = $this->calculateLossRate($defenderPower, $attackerPower);
        $defenderLossRate = $this->calculateLossRate($attackerPower, $defenderPower);

        [$attackerLosses, $attackerSurvivors] = $this->applyLossRate($attackerUnits, $attackerLossRate);
        [$defenderLosses, $defenderSurvivors] = $this->applyLossRate($defenderUnits, $defenderLossRate);

        $winner = 'draw';
        if ($attackerLossRate < $defenderLossRate) {
            $winner = 'attacker';
        } elseif ($defenderLossRate < $attackerLossRate) {
            $winner = 'defender';
        } elseif ($attackerPower !== $defenderPower) {
            // Equal loss rates but uneven power should still give the edge to the
            // army with higher initial power.
            $winner = $attackerPower > $defenderPower ? 'attacker' : 'defender';
        }

        return [
            'winner' => $winner,
            'attacker' => [
                'power' => $attackerPower,
                'lossRate' => $attackerLossRate,
                'losses' => $attackerLosses,
                'survivors' => $attackerSurvivors,
            ],
            'defender' => [
                'power' => $defenderPower,
                'lossRate' => $defenderLossRate,
                'losses' => $defenderLosses,
                'survivors' => $defenderSurvivors,
            ],
        ];
    }

    /**
     * Distribute loot among the surviving attackers.
     *
     * The method returns the resources taken, the remaining resources on the
     * defender, and details about the utilised capacity.
     */
    public function distributeLoot(array $attacker, array $defender, array $casualties): array
    {
        $attackerUnits = $this->extractUnits($attacker);
        $survivors = $casualties['attacker']['survivors'] ?? [];
        $defenderResources = $defender['resources'] ?? [];

        $totalCapacity = 0.0;
        foreach ($survivors as $unitKey => $count) {
            $unit = $attackerUnits[$unitKey] ?? [];
            $capacityPerUnit = $this->extractStat($unit, ['carry', 'capacity', 'load', 'loot'], 0.0);
            if ($capacityPerUnit <= 0 && isset($unit['carry_capacity'])) {
                $capacityPerUnit = (float) $unit['carry_capacity'];
            }

            $totalCapacity += max(0, $count) * max(0.0, $capacityPerUnit);
        }

        $totalCapacity = (int) floor($totalCapacity);
        $remainingCapacity = $totalCapacity;
        $stolenResources = [];

        foreach ($defenderResources as $resource => $amount) {
            if (!is_numeric($amount) || $remainingCapacity <= 0) {
                $stolenResources[$resource] = 0;
                continue;
            }

            $steal = (int) min((int) $amount, $remainingCapacity);
            $stolenResources[$resource] = $steal;
            $defenderResources[$resource] = (int) $amount - $steal;
            $remainingCapacity -= $steal;
        }

        return [
            'capacity' => [
                'total' => $totalCapacity,
                'used' => $totalCapacity - $remainingCapacity,
                'remaining' => $remainingCapacity,
            ],
            'resources' => $stolenResources,
            'defenderRemaining' => $defenderResources,
        ];
    }

    private function extractUnits(array $payload): array
    {
        if (isset($payload['units']) && is_array($payload['units'])) {
            return $payload['units'];
        }

        return $payload;
    }

    private function calculateArmyStrength(array $units, string $type): float
    {
        $total = 0.0;
        foreach ($units as $unit) {
            if (!is_array($unit)) {
                $count = is_numeric($unit) ? (int) $unit : 0;
                $total += $count;
                continue;
            }

            $count = $this->extractCount($unit);
            if ($count <= 0) {
                continue;
            }

            if ($type === 'attack') {
                $stat = $this->extractStat($unit, ['attack', 'offense', 'off', 'power'], 1.0);
            } else {
                if (isset($unit['defense'])) {
                    $stat = (float) $unit['defense'];
                } else {
                    $inf = $this->extractStat($unit, ['defense_inf', 'defence_inf', 'infantry_defense'], 0.0);
                    $cav = $this->extractStat($unit, ['defense_cav', 'defence_cav', 'cavalry_defense'], 0.0);
                    $stat = $inf > 0 || $cav > 0 ? max($inf, $cav) : $this->extractStat($unit, ['defense', 'def', 'shield', 'armor'], 1.0);
                }
            }

            $total += $count * max(0.0, $stat);
        }

        return $total;
    }

    private function extractCount($unit): int
    {
        if (!is_array($unit)) {
            return is_numeric($unit) ? (int) $unit : 0;
        }

        $keys = ['count', 'amount', 'quantity', 'qty', 'number', 'units'];
        foreach ($keys as $key) {
            if (isset($unit[$key])) {
                return max(0, (int) $unit[$key]);
            }
        }

        if (isset($unit['survivors'])) {
            return max(0, (int) $unit['survivors']);
        }

        return 0;
    }

    private function extractStat(array $unit, array $keys, float $default): float
    {
        foreach ($keys as $key) {
            if (isset($unit[$key])) {
                return (float) $unit[$key];
            }
        }

        if (isset($unit['stats']) && is_array($unit['stats'])) {
            foreach ($keys as $key) {
                if (isset($unit['stats'][$key])) {
                    return (float) $unit['stats'][$key];
                }
            }
        }

        return $default;
    }

    private function calculateLossRate(float $opponentPower, float $ownPower): float
    {
        if ($ownPower <= 0) {
            return 1.0;
        }

        if ($opponentPower <= 0) {
            return 0.0;
        }

        return max(0.0, min(1.0, $opponentPower / $ownPower));
    }

    private function applyLossRate(array $units, float $lossRate): array
    {
        $losses = [];
        $survivors = [];

        foreach ($units as $key => $unit) {
            $count = $this->extractCount($unit);
            $loss = (int) round($count * $lossRate);
            $loss = min($loss, $count);
            $losses[$key] = $loss;
            $survivors[$key] = $count - $loss;
        }

        return [$losses, $survivors];
    }

    private function zeroLosses(array $units, bool $losses = true): array
    {
        $result = [];
        foreach ($units as $key => $unit) {
            $count = $this->extractCount($unit);
            $result[$key] = $losses ? 0 : $count;
        }

        return $result;
    }
}
