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
use Illuminate\Support\Arr;

/**
 * Resolve combat encounters and persist their resulting reports.
 */
class ResolveCombatAction
{
    /**
     * Inject repositories that encapsulate combat resolution and report storage.
     */
    public function __construct(
        private CombatRepository $combatRepository,
        private ReportRepository $reportRepository,
        private CombatCalculator $combatCalculator,
    ) {
    }

    /**
     * Execute the combat resolution process and store the results.
     *
     * @param array<int, array<int, array<int, Movement>>> $arrivalsAtSecondGroupedByTarget
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function execute(array $arrivalsAtSecondGroupedByTarget = []): array
    {
        $results = [];

        foreach ($arrivalsAtSecondGroupedByTarget as $targetKid => $arrivalsBySecond) {
            $targetKid = (int) $targetKid;

            if (! is_array($arrivalsBySecond)) {
                continue;
            }

            ksort($arrivalsBySecond);

            $defenseState = $this->combatRepository->loadDefenseState($targetKid);
            $defenseVillage = $this->resolveDefenseVillage($defenseState, $targetKid);

            foreach ($arrivalsBySecond as $arrivalSecond => $movements) {
                if (! is_array($movements) || $movements === []) {
                    continue;
                }

                foreach ($movements as $movement) {
                    if (! $movement instanceof Movement) {
                        continue;
                    }

                    $combatResult = $this->resolveSingleMovement(
                        $movement,
                        $defenseState,
                        $defenseVillage,
                    );

                    $results[$targetKid][$arrivalSecond][$movement->getKey() ?? spl_object_id($movement)] = $combatResult;
                }
            }
        }

        return $results;
    }

    /**
     * Resolve a single combat movement against the shared defense state.
     *
     * @param array{village: Village|null, groups: list<array<string, mixed>>, owner_tribe: int|null, wall_level: int, wall_bonus: float} $defenseState
     * @return array<string, mixed>
     */
    private function resolveSingleMovement(
        Movement $movement,
        array &$defenseState,
        ?Village $defenseVillage
    ): array {
        $attackTypeLabel = $movement->attack_type === MovementService::ATTACKTYPE_RAID ? 'raid' : 'normal';
        $isRaid = $movement->attack_type === MovementService::ATTACKTYPE_RAID;

        $attackerUnits = $this->extractUnits($movement);
        $attackerProfile = $this->combatCalculator->offensiveProfile($attackerUnits, max(1, (int) $movement->race));

        $defenseProfile = $this->aggregateDefenseProfile($defenseState['groups']);
        $initialDefenderUnits = $this->aggregateUnits($defenseState['groups']);

        $originVillage = $this->combatRepository->findVillageByLegacyKid((int) $movement->kid);
        $moraleModifier = $this->combatCalculator->moraleModifier(
            $originVillage?->population,
            $defenseVillage?->population,
        );

        $seed = $this->resolveSeed($movement);
        $randomModifier = $this->combatCalculator->randomModifier($seed);

        $attackPower = $attackerProfile['total'] * $randomModifier;
        $defensePower = $this->combatCalculator->effectiveDefense(
            $defenseProfile,
            $attackerProfile,
            (int) ($defenseState['wall_level'] ?? 0),
            Arr::get($defenseState, 'owner_tribe'),
            (float) ($defenseState['wall_bonus'] ?? 0.0),
            $moraleModifier,
        );

        $casualtyRates = $this->combatCalculator->casualtyRates($attackPower, $defensePower, $isRaid);

        [$attackerLosses, $attackerSurvivors] = $this->applyLosses($attackerUnits, $casualtyRates['attacker']);

        $this->applyDefenderSurvivors($defenseState, $casualtyRates['defender']);

        $defenderSurvivors = $this->aggregateUnits($defenseState['groups']);
        $defenderLosses = $this->calculateLosses($initialDefenderUnits, $defenderSurvivors);

        $this->applyAttackerSurvivors($movement, $attackerSurvivors);

        $wallSnapshot = $this->applyWallDamage($movement, $defenseState, $defenseVillage, $attackerSurvivors, $isRaid);
        $buildingDamage = $this->applyBuildingDamage($movement, $defenseVillage, $attackerSurvivors, $isRaid);

        $attackerOutcome = $this->determineOutcome($casualtyRates, true);
        $defenderOutcome = $this->determineOutcome($casualtyRates, false);

        $attackerUserId = $this->resolveUserId((int) $movement->uid, $originVillage?->user_id);
        $defenderUserId = $defenseVillage?->user_id;

        $attackerPayload = $this->buildReportPayload(
            $attackerOutcome,
            $attackTypeLabel,
            $attackerUnits,
            $attackerLosses,
            $attackerSurvivors,
            $initialDefenderUnits,
            $defenderLosses,
            $defenderSurvivors,
            $moraleModifier,
            $randomModifier,
            $wallSnapshot,
            $buildingDamage,
            $casualtyRates,
        );

        $defenderPayload = $this->buildReportPayload(
            $defenderOutcome,
            $attackTypeLabel,
            $initialDefenderUnits,
            $defenderLosses,
            $defenderSurvivors,
            $attackerUnits,
            $attackerLosses,
            $attackerSurvivors,
            $moraleModifier,
            $randomModifier,
            $wallSnapshot,
            $buildingDamage,
            $casualtyRates,
        );

        $reports = $this->reportRepository->createCombatReports(
            $attackerUserId,
            $defenderUserId,
            $originVillage?->getKey(),
            $defenseVillage?->getKey(),
            $attackerPayload,
            $defenderPayload,
        );

        $this->combatRepository->markMovementProcessed($movement, [
            'battle' => [
                'outcome' => $attackerOutcome,
                'attack_type' => $attackTypeLabel,
                'morale_modifier' => $moraleModifier,
                'random_modifier' => $randomModifier,
                'wall_bonus' => (float) ($defenseState['wall_bonus'] ?? 0.0),
                'wall' => $wallSnapshot,
                'building_damage' => $buildingDamage,
                'casualty_rates' => $casualtyRates,
            ],
            'report_ids' => [
                'attacker' => Arr::get($reports, 'attacker')?->getKey(),
                'defender' => Arr::get($reports, 'defender')?->getKey(),
            ],
        ]);

        return [
            'outcome' => $attackerOutcome,
            'attack_type' => $attackTypeLabel,
            'reports' => $reports,
            'morale_modifier' => $moraleModifier,
            'random_modifier' => $randomModifier,
            'casualty_rates' => $casualtyRates,
            'wall' => $wallSnapshot,
            'building_damage' => $buildingDamage,
        ];
    }

    /**
     * Ensure the defense village is resolved consistently for future calculations.
     *
     * @param array{village: Village|null} $defenseState
     */
    private function resolveDefenseVillage(array &$defenseState, int $targetKid): ?Village
    {
        if ($defenseState['village'] instanceof Village) {
            return $defenseState['village'];
        }

        $village = $this->combatRepository->findVillageByLegacyKid($targetKid);
        $defenseState['village'] = $village;

        return $village;
    }

    /**
     * Extract movement unit slots into a normalized array.
     *
     * @return array<string, int>
     */
    private function extractUnits(Movement $movement): array
    {
        $units = [];

        foreach (range(1, 11) as $slot) {
            $key = 'u'.$slot;
            $units[$key] = (int) ($movement->{$key} ?? 0);
        }

        return $units;
    }

    /**
     * Aggregate the defensive profile across all groups.
     *
     * @param list<array{units: array<string, int>, race: int|null}> $groups
     * @return array{infantry: float, cavalry: float}
     */
    private function aggregateDefenseProfile(array $groups): array
    {
        $infantry = 0.0;
        $cavalry = 0.0;

        foreach ($groups as $group) {
            $tribe = $group['race'] ?? 1;
            $profile = $this->combatCalculator->defensiveProfile($group['units'], $tribe);
            $infantry += $profile['infantry'];
            $cavalry += $profile['cavalry'];
        }

        return ['infantry' => $infantry, 'cavalry' => $cavalry];
    }

    /**
     * Aggregate unit counts across all defensive groups.
     *
     * @param list<array{units: array<string, int>}> $groups
     * @return array<string, int>
     */
    private function aggregateUnits(array $groups): array
    {
        $totals = [];

        foreach (range(1, 11) as $slot) {
            $totals['u'.$slot] = 0;
        }

        foreach ($groups as $group) {
            foreach ($group['units'] as $slot => $count) {
                $totals[$slot] = ($totals[$slot] ?? 0) + (int) $count;
            }
        }

        return $totals;
    }

    /**
     * Apply proportional losses to a unit array.
     *
     * @param array<string, int> $units
     * @return array{0: array<string, int>, 1: array<string, int>}
     */
    private function applyLosses(array $units, float $lossRate): array
    {
        $losses = [];
        $survivors = [];

        $lossRate = max(0.0, min(1.0, $lossRate));

        foreach ($units as $slot => $count) {
            $count = max(0, (int) $count);
            $loss = (int) round($count * $lossRate);
            $loss = min($count, max(0, $loss));
            $losses[$slot] = $loss;
            $survivors[$slot] = $count - $loss;
        }

        return [$losses, $survivors];
    }

    /**
     * Persist defender casualties across all defense groups.
     *
     * @param array{groups: list<array<string, mixed>>, owner_tribe: int|null} $defenseState
     */
    private function applyDefenderSurvivors(array &$defenseState, float $lossRate): void
    {
        foreach ($defenseState['groups'] as $index => $group) {
            $groupUnits = $group['units'];
            [, $groupRemaining] = $this->applyLosses($groupUnits, $lossRate);

            $defenseState['groups'][$index]['units'] = $groupRemaining;

            if ($this->sumUnits($groupRemaining) > 0) {
                $defenseState['groups'][$index]['upkeep'] = $this->combatCalculator->upkeep(
                    $groupRemaining,
                    $group['race'] ?? ($defenseState['owner_tribe'] ?? 1),
                );

                $this->combatRepository->persistDefenseGroup($defenseState['groups'][$index]);
            } else {
                $this->combatRepository->deleteDefenseGroup($group);
                unset($defenseState['groups'][$index]);
            }
        }

        $defenseState['groups'] = array_values($defenseState['groups']);
    }

    /**
     * Persist attacker survivors on the movement record.
     *
     * @param array<string, int> $survivors
     */
    private function applyAttackerSurvivors(Movement $movement, array $survivors): void
    {
        foreach ($survivors as $slot => $count) {
            $movement->{$slot} = $count;
        }
    }

    /**
     * Resolve wall damage and keep a snapshot for reporting.
     *
     * @param array{wall_level: int|null, wall_bonus: float|null, village: Village|null, owner_tribe: int|null} $defenseState
     * @param array<string, int> $attackerSurvivors
     * @return array{before: int, after: int, damage: int, bonus: float}
     */
    private function applyWallDamage(
        Movement $movement,
        array &$defenseState,
        ?Village $defenseVillage,
        array $attackerSurvivors,
        bool $isRaid
    ): array {
        $before = (int) ($defenseState['wall_level'] ?? 0);
        $after = $before;

        if ($isRaid || $defenseVillage === null) {
            return [
                'before' => $before,
                'after' => $after,
                'damage' => 0,
                'bonus' => (float) ($defenseState['wall_bonus'] ?? 0.0),
            ];
        }

        $survivingRams = $attackerSurvivors['u7'] ?? 0;

        if ($survivingRams > 0) {
            $ramResult = $this->combatCalculator->applyRamDamage(
                $before,
                Arr::get($defenseState, 'owner_tribe'),
                $survivingRams,
            );

            if ($ramResult['damage'] > 0) {
                $reduction = $this->combatRepository->reduceWall($defenseVillage, $ramResult['damage']);
                $after = $reduction['after'];
                $defenseState['wall_level'] = $after;
            }
        }

        return [
            'before' => $before,
            'after' => $after,
            'damage' => max(0, $before - $after),
            'bonus' => (float) ($defenseState['wall_bonus'] ?? 0.0),
        ];
    }

    /**
     * Resolve building damage from catapult fire.
     *
     * @param array<string, int> $attackerSurvivors
     * @return list<array{position: int, before: int, after: int, damage: int}>
     */
    private function applyBuildingDamage(
        Movement $movement,
        ?Village $defenseVillage,
        array $attackerSurvivors,
        bool $isRaid
    ): array {
        if ($isRaid || $defenseVillage === null) {
            return [];
        }

        $survivingCatapults = $attackerSurvivors['u8'] ?? 0;

        if ($survivingCatapults <= 0 || $movement->ctar1 <= 0) {
            return [];
        }

        $damageLevels = $this->combatCalculator->catapultDamage($survivingCatapults);

        if ($damageLevels <= 0) {
            return [];
        }

        $reduction = $this->combatRepository->reduceBuildingLevel($defenseVillage, (int) $movement->ctar1, $damageLevels);
        $damage = max(0, $reduction['before'] - $reduction['after']);

        if ($damage <= 0) {
            return [];
        }

        return [[
            'position' => (int) $movement->ctar1,
            'before' => $reduction['before'],
            'after' => $reduction['after'],
            'damage' => $damage,
        ]];
    }

    /**
     * Determine the outcome string for a side.
     *
     * @param array{attacker: float, defender: float} $casualtyRates
     */
    private function determineOutcome(array $casualtyRates, bool $forAttacker): string
    {
        $attackerRate = $casualtyRates['attacker'];
        $defenderRate = $casualtyRates['defender'];

        $attackerVictory = $defenderRate >= $attackerRate;

        if ($forAttacker) {
            return $attackerVictory ? 'victory' : 'defeat';
        }

        return $attackerVictory ? 'defeat' : 'victory';
    }

    /**
     * Build a structured combat report payload.
     *
     * @param array<string, int> $primaryInitial
     * @param array<string, int> $primaryLosses
     * @param array<string, int> $primarySurvivors
     * @param array<string, int> $opponentInitial
     * @param array<string, int> $opponentLosses
     * @param array<string, int> $opponentSurvivors
     * @param array{attacker: float, defender: float} $casualtyRates
     * @return array<string, mixed>
     */
    private function buildReportPayload(
        string $outcome,
        string $attackType,
        array $primaryInitial,
        array $primaryLosses,
        array $primarySurvivors,
        array $opponentInitial,
        array $opponentLosses,
        array $opponentSurvivors,
        float $moraleModifier,
        float $randomModifier,
        array $wallSnapshot,
        array $buildingDamage,
        array $casualtyRates
    ): array {
        return [
            'outcome' => $outcome,
            'attack_type' => $attackType,
            'morale_modifier' => $moraleModifier,
            'random_modifier' => $randomModifier,
            'wall' => $wallSnapshot,
            'building_damage' => $buildingDamage,
            'attackers' => [
                'initial' => $primaryInitial,
                'losses' => $primaryLosses,
                'survivors' => $primarySurvivors,
            ],
            'defenders' => [
                'initial' => $opponentInitial,
                'losses' => $opponentLosses,
                'survivors' => $opponentSurvivors,
            ],
            'casualty_rates' => $casualtyRates,
        ];
    }

    /**
     * Resolve the randomness seed from the movement payload.
     */
    private function resolveSeed(Movement $movement): int
    {
        $data = $movement->data;

        if ($data !== null && trim($data) !== '') {
            $decoded = json_decode($data, true);

            if (is_array($decoded) && isset($decoded['seed']) && is_numeric($decoded['seed'])) {
                return (int) $decoded['seed'];
            }
        }

        return random_int(1, PHP_INT_MAX);
    }

    /**
     * Resolve a user identifier using the legacy UID fallback.
     */
    private function resolveUserId(int $legacyUid, ?int $fallbackId): ?int
    {
        if ($fallbackId !== null) {
            return $fallbackId;
        }

        if ($legacyUid <= 0) {
            return null;
        }

        return User::query()
            ->where('legacy_uid', $legacyUid)
            ->value('id');
    }

    /**
     * Calculate total losses given initial and surviving unit counts.
     *
     * @param array<string, int> $initial
     * @param array<string, int> $survivors
     * @return array<string, int>
     */
    private function calculateLosses(array $initial, array $survivors): array
    {
        $losses = [];

        foreach ($initial as $slot => $count) {
            $survivorCount = $survivors[$slot] ?? 0;
            $losses[$slot] = max(0, $count - $survivorCount);
        }

        foreach ($survivors as $slot => $count) {
            if (! array_key_exists($slot, $losses)) {
                $losses[$slot] = 0;
            }
        }

        return $losses;
    }

    /**
     * Sum total units in a payload for deletion decisions.
     *
     * @param array<string, int> $units
     */
    private function sumUnits(array $units): int
    {
        return array_sum(array_map(static fn (int $value): int => max(0, $value), $units));
    }
}
