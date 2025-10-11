<?php

namespace App\Services\Game;

use App\Models\Game\Enforcement;
use App\Models\Game\Movement;
use App\Models\Game\Trapped;
use DateTimeInterface;
use Illuminate\Support\Facades\Log;

class MovementService
{
    public const SORTTYPE_GOING = 0;
    public const SORTTYPE_RETURN = 1;

    public const ATTACKTYPE_SPY = 1;
    public const ATTACKTYPE_REINFORCEMENT = 2;
    public const ATTACKTYPE_NORMAL = 3;
    public const ATTACKTYPE_RAID = 4;
    public const ATTACKTYPE_SETTLERS = 5;
    public const ATTACKTYPE_EVASION = 6;
    public const ATTACKTYPE_ADVENTURE = 7;

    /**
     * @param array<int, int> $units
     */
    public function addMovement(
        int $kid,
        int $toKid,
        int $race,
        array $units,
        int $ctar1,
        int $ctar2,
        int $spyType,
        bool $redeployHero,
        int $mode,
        int $attackType,
        int|DateTimeInterface $startTime,
        int|DateTimeInterface $endTime,
        ?string $data = null,
    ): int {
        $movement = new Movement();

        $movement->fill(array_merge(
            [
                'kid' => $kid,
                'to_kid' => $toKid,
                'race' => $race,
                'ctar1' => $ctar1,
                'ctar2' => $ctar2,
                'spyType' => $spyType,
                'redeployHero' => $redeployHero,
                'mode' => $mode,
                'attack_type' => $attackType,
                'start_time' => $this->normalizeTimestamp($startTime),
                'end_time' => $this->normalizeTimestamp($endTime),
                'data' => $data ?? '',
                'markState' => 0,
                'proc' => 0,
            ],
            $this->normalizeUnits($units),
        ));

        if (! $movement->save()) {
            Log::warning('movement.insert_failed', [
                'kid' => $kid,
                'to_kid' => $toKid,
                'attack_type' => $attackType,
            ]);

            return 0;
        }

        return (int) $movement->getKey();
    }

    /**
     * @param array<int|string, mixed> $assignments
     */
    public function modifyMovement(int $id, array $assignments): bool
    {
        if ($id <= 0) {
            return false;
        }

        $normalized = $this->normalizeAssignments($assignments);

        if ($normalized === []) {
            return false;
        }

        return Movement::query()->whereKey($id)->update($normalized) > 0;
    }

    public function deleteMovement(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        return Movement::query()->whereKey($id)->delete() > 0;
    }

    public function deleteEnforce(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        return Enforcement::query()->whereKey($id)->delete() > 0;
    }

    public function deleteTrapped(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        return Trapped::query()->whereKey($id)->delete() > 0;
    }

    /**
     * @param array<int, int> $units
     */
    public function addTrapped(int $kid, int $toKid, int $race, array $units): void
    {
        $payload = array_merge([
            'kid' => $kid,
            'to_kid' => $toKid,
            'race' => $race,
        ], $this->normalizeUnits($units));

        $record = new Trapped($payload);
        $record->save();
    }

    public function isSameVillageReinforcementExists(int $kid, int $toKid): ?int
    {
        return Enforcement::query()
            ->where('kid', $kid)
            ->where('to_kid', $toKid)
            ->value('id');
    }

    public function isSameVillageTrappedExists(int $kid, int $toKid): ?int
    {
        return Trapped::query()
            ->where('kid', $kid)
            ->where('to_kid', $toKid)
            ->value('id');
    }

    public function setMovementMarkState(int $toKid, int $movementId, int $state): bool
    {
        if ($movementId <= 0) {
            return false;
        }

        $state = $state > 0 ? 1 : 0;

        return Movement::query()
            ->whereKey($movementId)
            ->where('to_kid', $toKid)
            ->where('mode', self::SORTTYPE_GOING)
            ->whereIn('attack_type', [self::ATTACKTYPE_NORMAL, self::ATTACKTYPE_RAID])
            ->update(['markState' => $state]) > 0;
    }

    /**
     * @param array<int, int> $units
     */
    public function addEnforce(int $uid, int $kid, int $toKid, int $race, array $units): bool
    {
        $payload = array_merge([
            'uid' => $uid,
            'kid' => $kid,
            'to_kid' => $toKid,
            'race' => $race,
        ], $this->normalizeUnits($units));

        $enforcement = new Enforcement($payload);

        return $enforcement->save();
    }

    /**
     * @param array<int, int>|array<string, int> $units
     * @return array<string, int>
     */
    protected function normalizeUnits(array $units): array
    {
        $payload = [];

        foreach ($this->unitColumns() as $index => $column) {
            if (array_key_exists($column, $units)) {
                $value = $units[$column];
            } elseif (array_key_exists($index + 1, $units)) {
                $value = $units[$index + 1];
            } else {
                $value = 0;
            }

            $payload[$column] = (int) $value;
        }

        return $payload;
    }

    /**
     * @param array<int|string, mixed> $assignments
     * @return array<string, mixed>
     */
    protected function normalizeAssignments(array $assignments): array
    {
        $normalized = [];

        foreach ($assignments as $key => $value) {
            if (is_int($key)) {
                if (! is_string($value) || ! str_contains($value, '=')) {
                    continue;
                }

                [$column, $raw] = explode('=', $value, 2);
                $column = trim($column);

                if ($column === '') {
                    continue;
                }

                $normalized[$column] = $this->castColumnValue($column, trim($raw));

                continue;
            }

            $normalized[$key] = $value;
        }

        return array_filter(
            $normalized,
            fn ($column) => in_array($column, $this->assignableColumns(), true),
            ARRAY_FILTER_USE_KEY,
        );
    }

    protected function castColumnValue(string $column, mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);

            if ($value === 'NULL' || $value === 'null') {
                return null;
            }

            if ($value !== '' && ($value[0] === "'" || $value[0] === '"')) {
                $quote = $value[0];
                if (str_ends_with($value, $quote)) {
                    $value = substr($value, 1, -1);
                }
            }
        }

        if (in_array($column, ['data'], true)) {
            return (string) $value;
        }

        if (in_array($column, ['redeployHero'], true)) {
            return (bool) $value;
        }

        if (in_array($column, $this->integerColumns(), true)) {
            return (int) $value;
        }

        return $value;
    }

    protected function normalizeTimestamp(int|float|DateTimeInterface $value): int
    {
        if ($value instanceof DateTimeInterface) {
            return $value->getTimestamp();
        }

        return (int) round((float) $value);
    }

    /**
     * @return array<int, string>
     */
    protected function unitColumns(): array
    {
        return [
            'u1',
            'u2',
            'u3',
            'u4',
            'u5',
            'u6',
            'u7',
            'u8',
            'u9',
            'u10',
            'u11',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function assignableColumns(): array
    {
        return array_merge([
            'kid',
            'to_kid',
            'race',
            'ctar1',
            'ctar2',
            'spyType',
            'redeployHero',
            'mode',
            'attack_type',
            'start_time',
            'end_time',
            'data',
            'markState',
            'proc',
        ], $this->unitColumns());
    }

    /**
     * @return array<int, string>
     */
    protected function integerColumns(): array
    {
        return array_merge([
            'kid',
            'to_kid',
            'race',
            'ctar1',
            'ctar2',
            'spyType',
            'mode',
            'attack_type',
            'start_time',
            'end_time',
            'markState',
            'proc',
        ], $this->unitColumns());
    }
}
