<?php

declare(strict_types=1);

namespace App\Services\Migration\Transforms;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class GameSummaryMetricRowTransformer
{
    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function transform(array $row): array
    {
        self::assertHasKeys($row, ['players_count']);

        return [
            'total_player_count' => self::normalizeCount($row['players_count'] ?? 0),
            'roman_player_count' => self::normalizeCount($row['roman_players_count'] ?? 0),
            'teuton_player_count' => self::normalizeCount($row['teuton_players_count'] ?? 0),
            'gaul_player_count' => self::normalizeCount($row['gaul_players_count'] ?? 0),
            'egyptian_player_count' => self::normalizeCount($row['egyptians_players_count'] ?? 0),
            'hun_player_count' => self::normalizeCount($row['huns_players_count'] ?? 0),
            'first_village_player_name' => self::normalizeNullableString($row['first_village_player_name'] ?? null),
            'first_village_recorded_at' => self::normalizeTimestamp($row['first_village_time'] ?? null),
            'first_artifact_player_name' => self::normalizeNullableString($row['first_art_player_name'] ?? null),
            'first_artifact_recorded_at' => self::normalizeTimestamp($row['first_art_time'] ?? null),
            'first_world_wonder_plan_player_name' => self::normalizeNullableString($row['first_ww_plan_player_name'] ?? null),
            'first_world_wonder_plan_recorded_at' => self::normalizeTimestamp($row['first_ww_plan_time'] ?? null),
            'first_world_wonder_player_name' => self::normalizeNullableString($row['first_ww_player_name'] ?? null),
            'first_world_wonder_recorded_at' => self::normalizeTimestamp($row['first_ww_time'] ?? null),
            'created_at' => CarbonImmutable::now(),
            'updated_at' => CarbonImmutable::now(),
        ];
    }

    protected static function assertHasKeys(array $row, array $keys): void
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $row)) {
                throw new InvalidArgumentException(sprintf('Missing required column [%s] in game summary row.', $key));
            }
        }
    }

    protected static function normalizeCount(mixed $value): int
    {
        $count = (int) ($value ?? 0);

        if ($count < 0) {
            throw new InvalidArgumentException('Summary counts cannot be negative.');
        }

        return $count;
    }

    protected static function normalizeNullableString(mixed $value): ?string
    {
        $string = $value !== null ? trim((string) $value) : null;

        return $string === '' ? null : $string;
    }

    protected static function normalizeTimestamp(mixed $value): ?CarbonImmutable
    {
        if ($value === null) {
            return null;
        }

        $timestamp = (int) $value;

        if ($timestamp <= 0) {
            return null;
        }

        return CarbonImmutable::createFromTimestampUTC($timestamp);
    }
}
