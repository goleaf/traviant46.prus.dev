<?php

namespace App\Services\Migration\Transforms;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class LoginIpLogRowTransformer
{
    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function transform(array $row): array
    {
        self::assertHasKeys($row, ['uid', 'ip', 'time']);

        $userId = (int) ($row['uid'] ?? 0);

        if ($userId <= 0) {
            throw new InvalidArgumentException('Login IP logs must reference a valid user.');
        }

        $numericAddress = (int) ($row['ip'] ?? 0);

        if ($numericAddress <= 0) {
            throw new InvalidArgumentException('Login IP logs must include a valid IPv4 address.');
        }

        $recordedTimestamp = (int) ($row['time'] ?? 0);

        if ($recordedTimestamp <= 0) {
            throw new InvalidArgumentException('Login IP logs must include a positive timestamp.');
        }

        $recordedAt = CarbonImmutable::createFromTimestampUTC($recordedTimestamp);
        $ipAddress = self::formatIp($numericAddress);

        return [
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'ip_address_numeric' => $numericAddress,
            'recorded_at' => $recordedAt,
            'created_at' => $recordedAt,
            'updated_at' => $recordedAt,
        ];
    }

    protected static function assertHasKeys(array $row, array $keys): void
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $row)) {
                throw new InvalidArgumentException(sprintf('Missing required column [%s] in login IP log row.', $key));
            }
        }
    }

    protected static function formatIp(int $numeric): string
    {
        $normalized = $numeric;

        if ($normalized < 0) {
            $normalized = ($normalized + 4294967296) % 4294967296;
        }

        $packed = pack('N', $normalized);
        $ip = inet_ntop($packed);

        if ($ip === false) {
            throw new InvalidArgumentException(sprintf('Unable to convert numeric IP [%d] to dotted notation.', $numeric));
        }

        return $ip;
    }
}
