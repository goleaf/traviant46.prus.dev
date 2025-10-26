<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Models\Game\Movement;
use App\Models\Game\Village;
use App\Models\User;
use App\Repositories\Game\CombatRepository;
use App\Repositories\Game\ReportRepository;
use App\Services\Game\CombatCalculator;
use App\Services\Game\MovementService;

class ResolveCombatAction
{
    public function __construct(
        private readonly CombatRepository $combat,
        private readonly ReportRepository $reports,
        private readonly CombatCalculator $calculator,
    ) {}

    /**
     * @param array<int|string, array<int|string, iterable<Movement>>> $arrivalsAtSecondGroupedByTarget
     */
    public function execute(array $arrivalsAtSecondGroupedByTarget): void
    {
        foreach ($arrivalsAtSecondGroupedByTarget as $targetKid => $arrivalsBySecond) {
            $targetKid = (int) $targetKid;

            if (! is_iterable($arrivalsBySecond)) {
                continue;
            }

            $defenseState = $this->combat->loadDefenseState($targetKid);
            $village = $defenseState['village'];
            $defenseGroups = $defenseState['groups'];
            $ownerTribe = $defenseState['owner_tribe'];
            $wallLevel = $defenseState['wall_level'];
            $wallBonus = $defenseState['wall_bonus'];

            foreach ($arrivalsBySecond as $arrivalSecond => $movements) {
                if (! is_iterable($movements)) {
                    continue;
                }

                foreach ($movements as $movement) {
                    if (! $movement instanceof Movement) {
                        continue;
                    }

                    $result = $this->resolveBattle(
                        movement: $movement,
                        defenseGroups: $defenseGroups,
                        ownerTribe: $ownerTribe,
                        wallLevel: $wallLevel,
                        wallBonus: $wallBonus,
                        arrivalSecond: (int) $arrivalSecond,
                        village: $village,
                    );

                    $defenseGroups = $result['defense_groups'];
                    $ownerTribe = $result['owner_tribe'];
                    $wallLevel = $result['wall_level'];
                    $wallBonus = $result['wall_bonus'];
                }
            }
        }
    }

    /**
     * @param list<array{type: string, kid: int, race: int|null, model: mixed, units: array<string, int>}> $defenseGroups
     * @return array{
     *     defense_groups: list<array{type: string, kid: int, race: int|null, model: mixed, units: array<string, int>}>,
     *     owner_tribe: int|null,
     *     wall_level: int,
     *     wall_bonus: float
     * }
     */
    private function resolveBattle(
        Movement $movement,
        array $defenseGroups,
        ?int $ownerTribe,
        int $wallLevel,
        float $wallBonus,
        int $arrivalSecond,
        ?Village $village
    ): array {
        $originVillage = null;
        $attackType = (int) $movement->attack_type;

        if (! in_array($attackType, [MovementService::ATTACKTYPE_NORMAL, MovementService::ATTACKTYPE_RAID], true)) {
            $this->combat->markMovementProcessed($movement, ['outcome' => 'skipped']);

            return [
                'defense_groups' => $defenseGroups,
                'owner_tribe' => $ownerTribe,
                'wall_level' => $wallLevel,
                'wall_bonus' => (float) $wallBonus,
            ];
        }

        $attackerUnits = $this->extractUnits($movement);
        if (array_sum($attackerUnits) <= 0) {
            $this->combat->markMovementProcessed($movement, ['outcome' => 'empty']);

            return [
                'defense_groups' => $defenseGroups,
                'owner_tribe' => $ownerTribe,
                'wall_level' => $wallLevel,
                'wall_bonus' => (float) $wallBonus,
            ];
        }

        $originVillage = $this->combat->findVillageByKid((int) $movement->kid);
        $attackerTribe = (int) $movement->race ?: 1;
        $attackerProfile = $this->calculator->offensiveProfile($attackerUnits, $attackerTribe);

        $defenseTotals = [
            'infantry' => 0.0,
            'cavalry' => 0.0,
        ];
        $defenseUnitTotals = [];

        foreach ($defenseGroups as $group) {
            $groupRace = $group['race'] ?? $ownerTribe ?? 1;
            $profile = $this->calculator->defensiveProfile($group['units'], $groupRace);
            $defenseTotals['infantry'] += $profile['infantry'];
            $defenseTotals['cavalry'] += $profile['cavalry'];

            foreach ($group['units'] as $slot => $count) {
                $defenseUnitTotals[$slot] = ($defenseUnitTotals[$slot] ?? 0) + $count;
            }
        }

        $randomSeed = $this->determineRandomSeed($movement, $arrivalSecond);
        $randomModifier = $this->calculator->randomModifier($randomSeed);
        $attackerPopulation = $originVillage?->population !== null ? (int) $originVillage->population : null;
        $defenderPopulation = $village?->population !== null ? (int) $village->population : null;
        $moraleModifier = $this->calculator->moraleModifier($attackerPopulation, $defenderPopulation);

        $attackPower = $attackerProfile['total'] * $randomModifier;
        $effectiveDefense = $this->calculator->effectiveDefense(
            defense: $defenseTotals,
            attackerProfile: $attackerProfile,
            wallLevel: $wallLevel,
            defenderTribe: $ownerTribe,
            wallBonus: $wallBonus,
            moraleModifier: $moraleModifier,
        );

        $casualtyRates = $this->calculator->casualtyRates(
            attackPower: $attackPower,
            defensePower: $effectiveDefense,
            isRaid: $attackType === MovementService::ATTACKTYPE_RAID,
        );

        $attackerLosses = $this->calculateLosses($attackerUnits, $casualtyRates['attacker']);
        $attackerSurvivors = $this->calculateSurvivors($attackerUnits, $attackerLosses);

        $defenderLosses = $this->calculateLosses($defenseUnitTotals, $casualtyRates['defender']);
        $defenseGroups = $this->distributeDefenderLosses($defenseGroups, $defenseUnitTotals, $defenderLosses);

        $defenderSurvivors = $this->aggregateUnits($defenseGroups);
        $battleOutcome = $this->determineOutcome($attackerSurvivors, $defenderSurvivors);

        foreach (range(1, 11) as $slot) {
            $key = 'u'.$slot;
            $movement->{$key} = $attackerSurvivors[$key] ?? 0;
        }

        $canDamageStructures = $attackType === MovementService::ATTACKTYPE_NORMAL;

        $wallBeforeRams = $wallLevel;

        if (
            $canDamageStructures
            && ($attackerSurvivors['u7'] ?? 0) > 0
            && $village !== null
        ) {
            $ramOutcome = $this->calculator->applyRamDamage($wallLevel, $ownerTribe, $attackerSurvivors['u7']);
            if ($ramOutcome['damage'] > 0) {
                $result = $this->combat->reduceWall($village, $ramOutcome['damage']);
                $wallLevel = $result['after'];
            }
        }

        $buildingDamage = [];
        if (
            $canDamageStructures
            && $this->sumUnits($attackerSurvivors) > 0
            && $village !== null
        ) {
            $catapultDamage = $this->calculator->catapultDamage($attackerSurvivors['u8'] ?? 0);

            if ($catapultDamage > 0) {
                $targets = array_filter([
                    (int) $movement->ctar1,
                    (int) $movement->ctar2,
                ]);

                foreach ($targets as $slotNumber) {
                    $result = $this->combat->reduceBuildingLevel($village, $slotNumber, $catapultDamage);

                    if ($result['before'] > $result['after']) {
                        $buildingDamage[] = [
                            'slot' => $slotNumber,
                            'before' => $result['before'],
                            'after' => $result['after'],
                        ];
                    }
                }
            }
        }

        $wallBonus = $this->resolveWallBonusFromVillage($village, $wallBonus);

        $this->persistDefenseGroups($defenseGroups, $ownerTribe);

        $defenseGroups = array_values(array_filter(
            $defenseGroups,
            static fn (array $group): bool => array_sum($group['units']) > 0,
        ));

        if ($ownerTribe === null && $village?->owner !== null) {
            $ownerTribe = (int) ($village->owner->race ?? $village->owner->tribe ?? 0) ?: null;
        }

        $attackerUserId = $this->resolveUserIdByLegacy($movement->uid);
        $defenderUserId = $village?->user_id;

        $attackerPayload = [
            'outcome' => $battleOutcome,
            'random_modifier' => $randomModifier,
            'morale_modifier' => $moraleModifier,
            'attack_power' => $attackPower,
            'defense_power' => $effectiveDefense,
            'units' => [
                'sent' => $attackerUnits,
                'losses' => $attackerLosses,
                'survivors' => $attackerSurvivors,
            ],
            'defenders' => [
                'initial' => $defenseUnitTotals,
                'losses' => $defenderLosses,
                'survivors' => $defenderSurvivors,
            ],
            'wall' => [
                'before' => $wallBeforeRams,
                'after' => $wallLevel,
                'bonus' => $wallBonus,
            ],
            'building_damage' => $buildingDamage,
        ];

        $defenderPayload = [
            'outcome' => $battleOutcome === 'victory' ? 'defeat' : ($battleOutcome === 'defeat' ? 'victory' : $battleOutcome),
            'morale_modifier' => $moraleModifier,
            'attack_power' => $attackPower,
            'defense_power' => $effectiveDefense,
            'attackers' => [
                'sent' => $attackerUnits,
                'losses' => $attackerLosses,
                'survivors' => $attackerSurvivors,
            ],
            'defenders' => [
                'initial' => $defenseUnitTotals,
                'losses' => $defenderLosses,
                'survivors' => $defenderSurvivors,
            ],
            'wall' => [
                'before' => $wallBeforeRams,
                'after' => $wallLevel,
                'bonus' => $wallBonus,
            ],
            'building_damage' => $buildingDamage,
        ];

        $this->reports->createCombatReports(
            attackerUserId: $attackerUserId,
            defenderUserId: $defenderUserId,
            originVillageId: $originVillage?->getKey(),
            targetVillageId: $village?->getKey(),
            attackerPayload: $attackerPayload,
            defenderPayload: $defenderPayload,
        );

        $this->combat->markMovementProcessed($movement, [
            'battle' => [
                'outcome' => $battleOutcome,
                'losses' => [
                    'attacker' => $attackerLosses,
                    'defender' => $defenderLosses,
                ],
                'survivors' => [
                    'attacker' => $attackerSurvivors,
                    'defender' => $defenderSurvivors,
                ],
                'random_modifier' => $randomModifier,
                'morale_modifier' => $moraleModifier,
                'wall_bonus' => $wallBonus,
            ],
        ]);

        $wallBonus = $this->resolveWallBonusFromVillage($village, $wallBonus);

        return [
            'defense_groups' => $defenseGroups,
            'owner_tribe' => $ownerTribe,
            'wall_level' => $wallLevel,
            'wall_bonus' => (float) $wallBonus,
        ];
    }

    /**
     * @param array<string, int> $units
     * @return array<string, int>
     */
    private function calculateLosses(array $units, float $lossRate): array
    {
        $losses = [];

        foreach ($units as $slot => $count) {
            if ($count <= 0) {
                $losses[$slot] = 0;

                continue;
            }

            $loss = (int) round($count * min(1.0, max(0.0, $lossRate)));
            $losses[$slot] = min($loss, $count);
        }

        return $losses;
    }

    /**
     * @param array<string, int> $units
     * @param array<string, int> $losses
     * @return array<string, int>
     */
    private function calculateSurvivors(array $units, array $losses): array
    {
        $survivors = [];

        foreach ($units as $slot => $count) {
            $loss = $losses[$slot] ?? 0;
            $survivors[$slot] = max(0, $count - $loss);
        }

        return $survivors;
    }

    /**
     * @param list<array{type: string, kid: int, race: int|null, model: mixed, units: array<string, int>}> $groups
     * @param array<string, int> $totals
     * @param array<string, int> $losses
     * @return list<array{type: string, kid: int, race: int|null, model: mixed, units: array<string, int>}>
     */
    private function distributeDefenderLosses(array $groups, array $totals, array $losses): array
    {
        foreach ($losses as $slot => $loss) {
            $total = $totals[$slot] ?? 0;
            if ($loss <= 0 || $total <= 0) {
                continue;
            }

            $maxLoss = min($loss, $total);
            $allocations = [];
            $fractions = [];
            $available = [];

            foreach ($groups as $index => $group) {
                $count = $group['units'][$slot] ?? 0;
                if ($count <= 0) {
                    $allocations[$index] = 0;
                    $fractions[$index] = 0.0;
                    $available[$index] = 0;

                    continue;
                }

                $proportion = ($count / $total) * $maxLoss;
                $base = min($count, (int) floor($proportion));
                $allocations[$index] = $base;
                $fractions[$index] = $proportion - $base;
                $available[$index] = $count;
            }

            $assigned = array_sum($allocations);
            $remaining = $maxLoss - $assigned;

            if ($remaining > 0) {
                arsort($fractions);
                foreach (array_keys($fractions) as $index) {
                    if ($remaining <= 0) {
                        break;
                    }

                    if ($available[$index] <= $allocations[$index]) {
                        continue;
                    }

                    $allocations[$index]++;
                    $remaining--;
                }
            }

            foreach ($groups as $index => &$group) {
                $count = $group['units'][$slot] ?? 0;
                $group['units'][$slot] = max(0, $count - ($allocations[$index] ?? 0));
            }
            unset($group);
        }

        return $groups;
    }

    /**
     * @param list<array{units: array<string, int>}> $groups
     * @return array<string, int>
     */
    private function aggregateUnits(array $groups): array
    {
        $totals = [];

        foreach ($groups as $group) {
            foreach ($group['units'] as $slot => $count) {
                $totals[$slot] = ($totals[$slot] ?? 0) + $count;
            }
        }

        return $totals;
    }

    /**
     * @param list<array{type: string, kid: int, race: int|null, model: mixed, units: array<string, int>}> $groups
     */
    private function persistDefenseGroups(array $groups, ?int $ownerTribe): void
    {
        foreach ($groups as $group) {
            $totalUnits = array_sum($group['units']);

            if ($totalUnits <= 0) {
                if ($group['type'] === 'reinforcement') {
                    $this->combat->deleteDefenseGroup($group);

                    continue;
                }

                $this->combat->persistDefenseGroup($group);

                continue;
            }

            if ($group['type'] === 'reinforcement') {
                $race = $group['race'] ?? $ownerTribe ?? 1;
                $group['upkeep'] = $this->calculator->upkeep($group['units'], (int) $race);
                $this->combat->deleteDefenseGroup($group);

                continue;
            }

            $this->combat->persistDefenseGroup($group);
        }
    }

    /**
     * @return array<string, int>
     */
    private function extractUnits(Movement $movement): array
    {
        $units = [];

        foreach (range(1, 11) as $slot) {
            $key = 'u'.$slot;
            $units[$key] = (int) $movement->{$key};
        }

        return $units;
    }

    private function sumUnits(array $units): int
    {
        return array_sum($units);
    }

    private function determineRandomSeed(Movement $movement, int $arrivalSecond): int
    {
        $data = $this->decodeMovementData($movement->data);

        if (isset($data['seed'])) {
            return (int) $data['seed'];
        }

        return abs(crc32(sprintf(
            '%d:%d:%d:%d',
            $movement->getKey(),
            $movement->kid,
            $movement->to_kid,
            $arrivalSecond,
        )));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeMovementData(?string $data): array
    {
        if ($data === null || trim($data) === '') {
            return [];
        }

        $decoded = json_decode($data, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function resolveWallBonusFromVillage(?Village $village, float $fallback): float
    {
        if ($village === null) {
            return $fallback;
        }

        $bonus = $village->defense_bonus;

        if (is_array($bonus) && array_key_exists('wall', $bonus)) {
            return (float) $bonus['wall'];
        }

        if (is_object($bonus) && isset($bonus->wall)) {
            return (float) $bonus->wall;
        }

        return $fallback;
    }

    private function determineOutcome(array $attackerSurvivors, array $defenderSurvivors): string
    {
        $attackerAlive = $this->sumUnits($attackerSurvivors) > 0;
        $defenderAlive = $this->sumUnits($defenderSurvivors) > 0;

        if ($attackerAlive && ! $defenderAlive) {
            return 'victory';
        }

        if (! $attackerAlive && $defenderAlive) {
            return 'defeat';
        }

        if (! $attackerAlive && ! $defenderAlive) {
            return 'mutual_destruction';
        }

        return 'standoff';
    }

    private function resolveUserIdByLegacy(?int $legacyUid): ?int
    {
        if ($legacyUid === null) {
            return null;
        }

        return User::query()
            ->where('legacy_uid', $legacyUid)
            ->value('id');
    }
}
