<?php

declare(strict_types=1);

namespace App\Services\Game;

use App\Data\Game\MovementPreview;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class MovementCalculator
{
    public function __construct(private readonly MapDistanceService $distances) {}

    /**
     * @param array<int, array{quantity:int,speed:int|null,upkeep:int|null}> $units
     */
    public function preview(
        int $originX,
        int $originY,
        int $targetX,
        int $targetY,
        array $units,
    ): ?MovementPreview {
        $activeUnits = array_filter($units, static fn (array $payload): bool => ($payload['quantity'] ?? 0) > 0);

        if ($activeUnits === []) {
            return null;
        }

        $speeds = array_values(array_filter(array_map(
            static fn (array $payload): ?int => Arr::get($payload, 'speed'),
            $activeUnits,
        )));

        if ($speeds === [] || in_array(null, $speeds, true)) {
            return null;
        }

        $slowestSpeed = min($speeds);
        if ($slowestSpeed <= 0) {
            return null;
        }

        $distance = $this->distances->distanceBetweenCoordinates($originX, $originY, $targetX, $targetY);

        $mapSpeed = max(1.0, (float) config('travian.connection.speed', 1));
        $movementModifier = max(0.1, (float) data_get(config('travian.settings'), 'game.movement_speed_increase', 1));
        $speedFactor = ($mapSpeed * $movementModifier) / 3600;

        $seconds = $distance <= 0
            ? 0
            : (int) ceil($distance / $slowestSpeed / $speedFactor);

        $departAt = Carbon::now();
        $arriveAt = (clone $departAt)->addSeconds($seconds);

        $upkeep = array_reduce($activeUnits, static function (int $carry, array $payload): int {
            $quantity = (int) ($payload['quantity'] ?? 0);
            $unitUpkeep = (int) ($payload['upkeep'] ?? 0);

            return $carry + ($quantity * $unitUpkeep);
        }, 0);

        $unitSpeeds = [];

        foreach ($units as $key => $payload) {
            $unitSpeeds[(string) $key] = Arr::has($payload, 'speed') && is_numeric($payload['speed'])
                ? (float) $payload['speed']
                : null;
        }

        return new MovementPreview(
            distance: $distance,
            durationSeconds: $seconds,
            slowestUnitSpeed: $slowestSpeed,
            speedFactor: $speedFactor,
            worldSpeedFactor: $mapSpeed,
            wrapAround: true,
            unitSpeeds: $unitSpeeds,
            departAt: $departAt,
            arriveAt: $arriveAt,
            returnAt: null,
            upkeep: $upkeep,
        );
    }
}
