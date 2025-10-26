<?php

declare(strict_types=1);

namespace App\Services\Migration\Transforms;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class WorldCasualtySnapshotRowTransformer
{
    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function transform(array $row): array
    {
        self::assertHasKeys($row, ['attacks', 'casualties', 'time']);

        $recordedTimestamp = (int) ($row['time'] ?? 0);

        if ($recordedTimestamp <= 0) {
            throw new InvalidArgumentException('World casualty rows must include a positive timestamp.');
        }

        $recordedAt = CarbonImmutable::createFromTimestampUTC($recordedTimestamp);

        return [
            'attack_count' => self::normalizeCount($row['attacks'] ?? 0),
            'casualty_count' => self::normalizeCount($row['casualties'] ?? 0),
            'recorded_at' => $recordedAt,
            'created_at' => $recordedAt,
            'updated_at' => $recordedAt,
        ];
    }

    protected static function assertHasKeys(array $row, array $keys): void
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $row)) {
                throw new InvalidArgumentException(sprintf('Missing required column [%s] in world casualty row.', $key));
            }
        }
    }

    protected static function normalizeCount(mixed $value): int
    {
        $count = (int) ($value ?? 0);

        if ($count < 0) {
            throw new InvalidArgumentException('World casualty counts cannot be negative.');
        }

        return $count;
    }
}
