<?php

namespace App\Services\Migration\Transforms;

use App\Enums\AttackMissionType;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

class AttackDispatchRowTransformer
{
    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function transform(array $row): array
    {
        self::assertHasKeys($row, ['timestamp', 'timestamp_checksum', 'to_kid', 'attack_type']);

        $arrivalTimestamp = (int) ($row['timestamp'] ?? 0);

        if ($arrivalTimestamp <= 0) {
            throw new InvalidArgumentException('Attack dispatch rows must include a positive timestamp.');
        }

        $arrivalChecksum = trim((string) $row['timestamp_checksum']);

        if ($arrivalChecksum === '' || strlen($arrivalChecksum) !== 6) {
            throw new InvalidArgumentException('Attack dispatch rows must include a six character checksum.');
        }

        $targetVillageId = (int) ($row['to_kid'] ?? 0);

        if ($targetVillageId <= 0) {
            throw new InvalidArgumentException('Attack dispatch rows must target a valid village.');
        }

        $arrival = CarbonImmutable::createFromTimestampUTC($arrivalTimestamp);
        $attackType = AttackMissionType::fromLegacyValue((int) ($row['attack_type'] ?? 0));

        return [
            'target_village_id' => $targetVillageId,
            'arrives_at' => $arrival,
            'arrival_checksum' => $arrivalChecksum,
            'unit_slot_one_count' => self::normalizeCount($row['u1'] ?? 0),
            'unit_slot_two_count' => self::normalizeCount($row['u2'] ?? 0),
            'unit_slot_three_count' => self::normalizeCount($row['u3'] ?? 0),
            'unit_slot_four_count' => self::normalizeCount($row['u4'] ?? 0),
            'unit_slot_five_count' => self::normalizeCount($row['u5'] ?? 0),
            'unit_slot_six_count' => self::normalizeCount($row['u6'] ?? 0),
            'unit_slot_seven_count' => self::normalizeCount($row['u7'] ?? 0),
            'unit_slot_eight_count' => self::normalizeCount($row['u8'] ?? 0),
            'unit_slot_nine_count' => self::normalizeCount($row['u9'] ?? 0),
            'unit_slot_ten_count' => self::normalizeCount($row['u10'] ?? 0),
            'includes_hero' => self::normalizeBool($row['u11'] ?? 0),
            'attack_type' => $attackType,
            'redeploy_hero' => self::normalizeBool($row['redeployHero'] ?? 0),
            'created_at' => $arrival,
            'updated_at' => $arrival,
        ];
    }

    protected static function assertHasKeys(array $row, array $keys): void
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $row)) {
                throw new InvalidArgumentException(sprintf('Missing required column [%s] in attack dispatch row.', $key));
            }
        }
    }

    protected static function normalizeCount(mixed $value): int
    {
        $count = (int) ($value ?? 0);

        if ($count < 0) {
            throw new InvalidArgumentException('Unit counts cannot be negative.');
        }

        return $count;
    }

    protected static function normalizeBool(mixed $value): bool
    {
        return (int) ($value ?? 0) > 0;
    }
}
