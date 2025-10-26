<?php

declare(strict_types=1);

namespace App\Services\Game;

use App\Models\Game\TroopType;
use App\Models\Game\Village;
use App\Support\Travian\UnitCatalog;
use App\ValueObjects\Travian\MapSize;
use Illuminate\Support\Arr;

class MapDistanceService
{
    public function __construct(
        private readonly MapSize $mapSize,
        private readonly UnitCatalog $unitCatalog,
    ) {}

    public function distanceBetweenCoordinates(
        int $originX,
        int $originY,
        int $targetX,
        int $targetY,
        bool $wrapAround = true,
    ): float {
        $dx = abs($originX - $targetX);
        $dy = abs($originY - $targetY);

        if ($wrapAround) {
            $diameter = (2 * $this->mapRadius()) + 1;
            $dx = $this->wrappedDelta($dx, $diameter);
            $dy = $this->wrappedDelta($dy, $diameter);
        }

        return hypot((float) $dx, (float) $dy);
    }

    public function distanceBetweenVillages(
        Village $origin,
        Village $target,
        ?bool $wrapAround = null,
    ): float {
        $wrap = $this->shouldWrap($wrapAround, $origin, $target);

        return $this->distanceBetweenCoordinates(
            (int) $origin->x_coordinate,
            (int) $origin->y_coordinate,
            (int) $target->x_coordinate,
            (int) $target->y_coordinate,
            $wrap,
        );
    }

    /**
     * @param array<string|int, mixed> $composition
     * @return array{
     *     distance: float,
     *     wrap_around: bool,
     *     unit_speeds: array<string, ?float>,
     *     slowest_unit_speed: ?float,
     *     world_speed_factor: float
     * }
     */
    public function profile(
        Village $origin,
        Village $target,
        array $composition = [],
        ?bool $wrapAround = null,
    ): array {
        $wrap = $this->shouldWrap($wrapAround, $origin, $target);
        $distance = $this->distanceBetweenCoordinates(
            (int) $origin->x_coordinate,
            (int) $origin->y_coordinate,
            (int) $target->x_coordinate,
            (int) $target->y_coordinate,
            $wrap,
        );

        $unitSpeeds = $composition === []
            ? []
            : $this->perUnitSpeeds($origin, $composition);

        $slowest = $unitSpeeds === []
            ? null
            : $this->slowestUnitSpeed($unitSpeeds, $composition);

        return [
            'distance' => $distance,
            'wrap_around' => $wrap,
            'unit_speeds' => $unitSpeeds,
            'slowest_unit_speed' => $slowest,
            'world_speed_factor' => $this->worldSpeedFactor($origin, $target),
        ];
    }

    public function worldSpeedFactor(Village $origin, ?Village $target = null): float
    {
        $origin->loadMissing('world');

        if ($target !== null) {
            $target->loadMissing('world');
        }

        $candidates = [
            $origin->world?->speed,
            $target?->world?->speed,
            config('travian.settings.game.speed'),
            config('travian.connection.speed'),
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate)) {
                $value = (float) $candidate;
                if ($value > 0.0) {
                    return $value;
                }
            }
        }

        return 1.0;
    }

    /**
     * @param array<string|int, mixed> $composition
     * @return array<string, ?float>
     */
    public function perUnitSpeeds(Village $origin, array $composition): array
    {
        $origin->loadMissing('owner');

        $speeds = [];
        $slotRequests = [];
        $typeIds = [];

        foreach ($composition as $key => $value) {
            $keyString = (string) $key;

            if (is_array($value) && array_key_exists('speed', $value)) {
                $speeds[$keyString] = is_numeric($value['speed'] ?? null)
                    ? (float) $value['speed']
                    : null;

                continue;
            }

            $slot = $this->normalizeSlot($key);

            if ($slot !== null && str_starts_with((string) $key, 'u')) {
                $slotRequests[$keyString] = $slot;

                continue;
            }

            if ($slot !== null && ! str_starts_with((string) $key, 'u')) {
                $typeIds[$keyString] = $slot;

                continue;
            }

            if (is_numeric($key)) {
                $typeIds[$keyString] = (int) $key;

                continue;
            }

            $speeds[$keyString] = null;
        }

        $tribe = $this->resolveTribe($origin);

        if ($tribe !== null && $slotRequests !== []) {
            foreach ($slotRequests as $key => $slot) {
                $speed = $this->unitCatalog->speedForSlot($tribe, $slot);
                $speeds[$key] = $speed !== null ? (float) $speed : null;
            }
        }

        if ($typeIds !== []) {
            $lookup = TroopType::query()
                ->whereIn('id', array_values($typeIds))
                ->pluck('speed', 'id')
                ->map(fn ($value) => $value !== null ? (float) $value : null)
                ->all();

            foreach ($typeIds as $key => $typeId) {
                $speeds[$key] = $lookup[$typeId] ?? ($speeds[$key] ?? null);
            }
        }

        ksort($speeds, SORT_STRING);

        return $speeds;
    }

    /**
     * @param array<string, ?float> $unitSpeeds
     * @param array<string|int, mixed> $composition
     */
    private function slowestUnitSpeed(array $unitSpeeds, array $composition): ?float
    {
        $candidates = [];

        foreach ($composition as $key => $value) {
            $quantity = $this->extractQuantity($value);

            if ($quantity <= 0) {
                continue;
            }

            $keyString = (string) $key;
            $speed = $unitSpeeds[$keyString] ?? null;

            if ($speed !== null && $speed > 0) {
                $candidates[] = (float) $speed;
            }
        }

        if ($candidates === []) {
            return null;
        }

        return min($candidates);
    }

    private function shouldWrap(?bool $override, ?Village $origin, ?Village $target): bool
    {
        if ($override !== null) {
            return $override;
        }

        $flags = [];

        if ($origin !== null) {
            $origin->loadMissing('world');
            $flags[] = $this->extractWrapFlag($origin->world?->features);
        }

        if ($target !== null) {
            $target->loadMissing('world');
            $flags[] = $this->extractWrapFlag($target->world?->features);
        }

        foreach ($flags as $flag) {
            if ($flag !== null) {
                return $flag;
            }
        }

        return (bool) config('travian.settings.game.wrap_around', true);
    }

    private function extractWrapFlag(mixed $features): ?bool
    {
        if (! is_array($features)) {
            return null;
        }

        foreach (['map.wrap_around', 'map.wrapAround', 'map.wrap'] as $path) {
            $value = Arr::get($features, $path);
            if ($value !== null) {
                return (bool) $value;
            }
        }

        return null;
    }

    private function resolveTribe(Village $village): ?int
    {
        $tribe = $village->owner?->tribe ?? $village->owner?->race;

        if ($tribe === null) {
            return null;
        }

        if (! is_numeric($tribe)) {
            return null;
        }

        $value = (int) $tribe;

        return $value > 0 ? $value : null;
    }

    private function mapRadius(): int
    {
        $radius = $this->mapSize->toInt();

        if ($radius <= 0) {
            $radius = (int) (config('travian.dynamic.map_size') ?? 0);
        }

        if ($radius <= 0) {
            $radius = (int) (config('travian.settings.game.map_size') ?? 400);
        }

        return max(1, $radius);
    }

    private function wrappedDelta(int $delta, int $diameter): int
    {
        if ($diameter <= 0) {
            return max(0, $delta);
        }

        $normalized = $delta % $diameter;

        if ($normalized < 0) {
            $normalized += $diameter;
        }

        return min($normalized, max(0, $diameter - $normalized));
    }

    private function normalizeSlot(string|int $key): ?int
    {
        if (is_int($key)) {
            return $key;
        }

        $trimmed = trim($key);

        if ($trimmed === '') {
            return null;
        }

        if (str_starts_with($trimmed, 'u')) {
            $trimmed = substr($trimmed, 1);
        }

        if (! is_numeric($trimmed)) {
            return null;
        }

        return (int) $trimmed;
    }

    private function extractQuantity(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        if (is_array($value)) {
            foreach (['quantity', 'count', 'amount', 'value'] as $key) {
                if (isset($value[$key]) && is_numeric($value[$key])) {
                    return (int) $value[$key];
                }
            }
        }

        return 0;
    }
}
