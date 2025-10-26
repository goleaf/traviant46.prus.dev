<?php

declare(strict_types=1);

namespace App\Services;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Utility service for handling the Travian like resource calculations.
 *
 * The service focuses on deterministic calculations so that it can be used
 * safely inside tests without relying on any database state.
 */
class ResourceService
{
    /**
     * Ordered list of the supported resource keys.
     *
     * @var array<int, string>
     */
    private const RESOURCE_KEYS = ['wood', 'clay', 'iron', 'crop'];

    /**
     * Calculate the production per hour for every resource.
     *
     * The method accepts flexible input so that both unit tests and legacy
     * callers can provide the modifiers in the format that is the most
     * convenient for them:
     *
     *  - `$percentageBonuses` and `$flatBonuses` can either be numeric values
     *    (applied globally) or associative arrays keyed by the resource name
     *    and/or the special keys `all`, `*` or `global`.
     *  - `$cropUpkeep` can either be a numeric value or an array with the same
     *    semantics as bonuses.  Only the crop resource is affected.
     *  - When a single associative array is supplied as the second argument it
     *    is treated as an options array and may contain the keys `percent`,
     *    `flat`, `upkeep` and `options`.
     *
     * The result is always rounded to the requested precision (defaults to
     * four decimal places) and negative values are clamped to zero except for
     * crop when explicitly allowed in the provided options.
     *
     * @param array<string, float|int> $baseProduction   Base production per hour.
     * @param array<string, mixed>|float|int $percentageBonuses Optional percentage modifiers.
     * @param array<string, mixed>|float|int $flatBonuses Optional flat modifiers.
     * @param array<string, mixed>|float|int $cropUpkeep Optional crop upkeep value(s).
     * @param array<string, mixed> $options Additional behaviour flags.
     *
     * @return array<string, float>
     */
    public function calculateProduction(
        array $baseProduction,
        array|float|int $percentageBonuses = [],
        array|float|int $flatBonuses = [],
        array|float|int $cropUpkeep = 0,
        array $options = []
    ): array {
        if (func_num_args() === 2 && $this->looksLikeOptions($percentageBonuses)) {
            /** @var array<string, mixed> $config */
            $config = is_array($percentageBonuses) ? $percentageBonuses : [];
            $percentageBonuses = $config['percent'] ?? $config['percentage'] ?? [];
            $flatBonuses = $config['flat'] ?? $config['flat_bonus'] ?? [];
            $cropUpkeep = $config['upkeep'] ?? $config['crop_upkeep'] ?? 0;
            $options = array_merge($options, $config['options'] ?? []);
        }

        $percentages = $this->normaliseModifier($percentageBonuses);
        $flats = $this->normaliseModifier($flatBonuses);
        $upkeepMap = $this->normaliseModifier($cropUpkeep);

        $precision = $this->extractPrecision($options, 4);
        $allowNegativeCrop = (bool)($options['allow_negative_crop'] ?? false);
        $minimum = (float)($options['minimum'] ?? 0.0);
        $globalMultiplier = (float)($options['global_multiplier'] ?? 1.0);

        $result = [];
        foreach (self::RESOURCE_KEYS as $resource) {
            $base = (float)($baseProduction[$resource] ?? 0);
            $flat = $this->valueForResource($flats, $resource);
            $percent = $this->valueForResource($percentages, $resource);

            $value = ($base + $flat) * (1 + $percent / 100);

            if ($resource === 'crop') {
                $value -= $this->valueForResource($upkeepMap, $resource);
            }

            $value *= $globalMultiplier;

            if ($resource !== 'crop' || !$allowNegativeCrop) {
                $value = max($value, $minimum);
            }

            $result[$resource] = $precision === null ? $value : round($value, $precision);
        }

        return $result;
    }

    /**
     * Update the resource amounts after a certain time span.
     *
     * @param array<string, float|int> $resources Current resource amounts.
     * @param array<string, float|int> $production Production per hour for each resource.
     * @param DateInterval|DateTimeInterface|array<int, DateTimeInterface>|array<string, DateTimeInterface>|float|int $elapsed
     *        Either a numeric value representing the elapsed seconds, a
     *        DateInterval instance or an array/tuple of DateTimeInterface
     *        instances where the first value represents the start time and the
     *        second value the end time.
     * @param array<string, mixed> $options Options, supports the same flags as
     *        {@see calculateProduction} plus `storage` and `storage_options`.
     *
     * @return array{
     *     resources: array<string, float>,
     *     overflow: array<string, float>,
     *     hadOverflow: bool,
     *     elapsed_seconds: float
     * }
     */
    public function updateResources(
        array $resources,
        array $production,
        DateInterval|DateTimeInterface|array|float|int $elapsed,
        array $options = []
    ): array {
        $elapsedSeconds = $this->normaliseElapsed($elapsed);
        $precision = $this->extractPrecision($options, 4);
        $allowNegativeCrop = (bool)($options['allow_negative_crop'] ?? false);
        $minimum = (float)($options['minimum'] ?? 0.0);

        $updated = [];
        foreach (self::RESOURCE_KEYS as $resource) {
            $current = (float)($resources[$resource] ?? 0);
            $perHour = (float)($production[$resource] ?? 0);
            $value = $current + $perHour * ($elapsedSeconds / 3600);

            if ($resource !== 'crop' || !$allowNegativeCrop) {
                $value = max($value, $minimum);
            }

            $updated[$resource] = $precision === null ? $value : round($value, $precision);
        }

        $overflow = array_fill_keys(self::RESOURCE_KEYS, 0.0);
        $hadOverflow = false;

        if (isset($options['storage'])) {
            $storageOptions = array_merge($options, $options['storage_options'] ?? []);
            $storageResult = $this->checkStorage($updated, (array)$options['storage'], $storageOptions);
            $updated = $storageResult['resources'];
            $overflow = $storageResult['overflow'];
            $hadOverflow = $storageResult['hadOverflow'];
        }

        return [
            'resources' => $updated,
            'overflow' => $overflow,
            'hadOverflow' => $hadOverflow,
            'elapsed_seconds' => $elapsedSeconds,
        ];
    }

    /**
     * Clamp the resources to the provided storage capacities.
     *
     * @param array<string, float|int> $resources Resource amounts to validate.
     * @param array<string, float|int> $storage Storage capacities. Keys can be
     *        the resource names or the aggregated keys `warehouse`, `granary`
     *        or `all`/`*`.
     * @param array<string, mixed> $options Behaviour overrides.
     *
     * @return array{
     *     resources: array<string, float>,
     *     overflow: array<string, float>,
     *     hadOverflow: bool
     * }
     */
    public function checkStorage(array $resources, array $storage, array $options = []): array
    {
        $precision = $this->extractPrecision($options, 4);
        $allowNegativeCrop = (bool)($options['allow_negative_crop'] ?? false);
        $minimum = (float)($options['minimum'] ?? 0.0);

        $capacities = $this->normaliseStorage($storage);

        $clamped = [];
        $overflow = [];
        $hadOverflow = false;

        foreach (self::RESOURCE_KEYS as $resource) {
            $value = (float)($resources[$resource] ?? 0);
            $capacity = $capacities[$resource] ?? null;

            if ($resource !== 'crop' || !$allowNegativeCrop) {
                $value = max($value, $minimum);
            }

            if ($capacity !== null && $value > $capacity) {
                $overflowAmount = $value - $capacity;
                $value = $capacity;
                $hadOverflow = true;
            } else {
                $overflowAmount = 0.0;
            }

            $clamped[$resource] = $precision === null ? $value : round($value, $precision);
            $overflow[$resource] = $precision === null ? $overflowAmount : round($overflowAmount, $precision);
        }

        return [
            'resources' => $clamped,
            'overflow' => $overflow,
            'hadOverflow' => $hadOverflow,
        ];
    }

    /**
     * @param array<string, mixed>|float|int $modifier
     *
     * @return array<string, float>
     */
    private function normaliseModifier(array|float|int $modifier): array
    {
        if (!is_array($modifier)) {
            return ['all' => (float)$modifier];
        }

        $normalised = [];
        foreach ($modifier as $key => $value) {
            if (!is_numeric($value)) {
                continue;
            }
            $normalised[(string)$key] = (float)$value;
        }

        return $normalised;
    }

    /**
     * @return float|null
     */
    private function extractPrecision(array &$options, ?int $default): ?int
    {
        if (array_key_exists('precision', $options)) {
            $precision = $options['precision'];
            unset($options['precision']);
            if ($precision === null) {
                return null;
            }
            if (!is_int($precision)) {
                throw new InvalidArgumentException('The precision option must be an integer or null.');
            }
            return $precision;
        }

        return $default;
    }

    private function normaliseElapsed(DateInterval|DateTimeInterface|array|float|int $elapsed): float
    {
        if ($elapsed instanceof DateInterval) {
            return $this->intervalToSeconds($elapsed);
        }

        if ($elapsed instanceof DateTimeInterface) {
            $now = new DateTimeImmutable();
            return max(0.0, (float)$now->getTimestamp() - (float)$elapsed->getTimestamp());
        }

        if (is_array($elapsed)) {
            $from = $elapsed['from'] ?? $elapsed[0] ?? null;
            $to = $elapsed['to'] ?? $elapsed[1] ?? null;

            if ($from instanceof DateTimeInterface && $to instanceof DateTimeInterface) {
                return max(0.0, (float)$to->getTimestamp() - (float)$from->getTimestamp());
            }

            if (is_numeric($from) && is_numeric($to)) {
                return max(0.0, (float)$to - (float)$from);
            }

            throw new InvalidArgumentException('Unable to determine elapsed seconds from the provided array.');
        }

        if (!is_numeric($elapsed)) {
            throw new InvalidArgumentException('Elapsed time must be numeric, a DateInterval or DateTime pair.');
        }

        return max(0.0, (float)$elapsed);
    }

    /**
     * @param array<string, float|int> $storage
     *
     * @return array<string, float|null>
     */
    private function normaliseStorage(array $storage): array
    {
        $storage = array_change_key_case($storage, CASE_LOWER);

        $result = array_fill_keys(self::RESOURCE_KEYS, null);

        foreach (self::RESOURCE_KEYS as $resource) {
            if (isset($storage[$resource]) && is_numeric($storage[$resource])) {
                $result[$resource] = (float)$storage[$resource];
            }
        }

        $warehouse = isset($storage['warehouse']) && is_numeric($storage['warehouse'])
            ? (float)$storage['warehouse']
            : null;
        $granary = isset($storage['granary']) && is_numeric($storage['granary'])
            ? (float)$storage['granary']
            : null;
        $global = isset($storage['all']) && is_numeric($storage['all'])
            ? (float)$storage['all']
            : (isset($storage['*']) && is_numeric($storage['*']) ? (float)$storage['*'] : null);

        foreach (['wood', 'clay', 'iron'] as $resource) {
            if ($result[$resource] === null && $warehouse !== null) {
                $result[$resource] = $warehouse;
            }
        }

        if ($result['crop'] === null && $granary !== null) {
            $result['crop'] = $granary;
        }

        if ($global !== null) {
            foreach (self::RESOURCE_KEYS as $resource) {
                if ($result[$resource] === null) {
                    $result[$resource] = $global;
                }
            }
        }

        return $result;
    }

    /**
     * @param array<string, float> $modifier
     */
    private function valueForResource(array $modifier, string $resource): float
    {
        $value = $modifier[$resource] ?? 0.0;
        $value += $modifier['all'] ?? 0.0;
        $value += $modifier['*'] ?? 0.0;
        $value += $modifier['global'] ?? 0.0;

        if ($resource !== 'crop') {
            $value += $modifier['warehouse'] ?? 0.0;
        } else {
            $value += $modifier['granary'] ?? 0.0;
        }

        return (float)$value;
    }

    private function looksLikeOptions(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        $candidateKeys = ['percent', 'percentage', 'flat', 'flat_bonus', 'upkeep', 'crop_upkeep', 'options'];
        foreach ($candidateKeys as $key) {
            if (array_key_exists($key, $value)) {
                return true;
            }
        }

        return false;
    }

    private function intervalToSeconds(DateInterval $interval): float
    {
        $reference = new DateTimeImmutable('@0');
        $adjusted = $reference->add($interval);

        return (float)$adjusted->getTimestamp() - (float)$reference->getTimestamp();
    }
}
