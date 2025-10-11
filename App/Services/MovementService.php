<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attack;
use App\Models\MovementOrder;
use App\Models\User;
use App\Models\Village;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class MovementService
{
    /**
     * Create a new attack movement originating from one village and heading towards another.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function sendAttack(array $attributes): MovementOrder
    {
        $origin = $this->resolveVillage(Arr::get($attributes, 'origin'), 'origin');
        $target = $this->resolveVillage(Arr::get($attributes, 'target'), 'target');
        $attacker = $this->resolveUser(Arr::get($attributes, 'attacker'), $origin, 'attacker');
        $defender = $this->resolveUser(Arr::get($attributes, 'defender'), $target, 'defender', allowNull: true);

        $units = $this->normaliseUnits(Arr::get($attributes, 'units', []));

        if ($units === []) {
            throw new InvalidArgumentException('At least one unit must be supplied when sending an attack.');
        }

        $options = (array) Arr::get($attributes, 'options', []);
        $type = strtolower((string) Arr::get($attributes, 'type', 'raid'));
        $movementStatus = (string) Arr::get($attributes, 'status', 'en_route');
        $attackStatus = (string) Arr::get($attributes, 'attack_status', 'marching');

        $departAt = $this->resolveDateTime(Arr::get($attributes, 'depart_at'));
        $speed = $this->resolveMovementSpeed($attributes, $units);
        $speedMultiplier = $this->resolveSpeedMultiplier($attributes);

        $timing = $this->calculateArrival($origin, $target, $speed, $departAt, $speedMultiplier);

        $attack = new Attack([
            'attacker_id' => $attacker?->getKey(),
            'defender_id' => $defender?->getKey(),
            'origin_village_id' => $origin->getKey(),
            'target_village_id' => $target->getKey(),
            'type' => $type,
            'status' => $attackStatus,
            'payload' => [
                'units' => $units,
                'options' => $options,
            ],
            'launched_at' => $timing['depart_at'],
            'arrives_at' => $timing['arrive_at'],
            'travel_time' => $timing['travel_time'],
        ]);

        $attack->save();

        $movement = new MovementOrder([
            'user_id' => $attacker?->getKey(),
            'origin_village_id' => $origin->getKey(),
            'target_village_id' => $target->getKey(),
            'movement_type' => $this->resolveMovementType($type),
            'status' => $movementStatus,
            'depart_at' => $timing['depart_at'],
            'arrive_at' => $timing['arrive_at'],
            'payload' => [
                'attack_id' => $attack->getKey(),
                'units' => $units,
                'options' => $options,
                'defender_id' => $defender?->getKey(),
                'travel_time' => $timing['travel_time'],
            ],
        ]);

        $movement->save();

        return $movement;
    }

    /**
     * Queue a reinforcement wave between two villages.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function sendReinforcement(array $attributes): MovementOrder
    {
        $origin = $this->resolveVillage(Arr::get($attributes, 'origin'), 'origin');
        $target = $this->resolveVillage(Arr::get($attributes, 'target'), 'target');
        $user = $this->resolveUser(Arr::get($attributes, 'user'), $origin, 'user');

        $units = $this->normaliseUnits(Arr::get($attributes, 'units', []));

        if ($units === []) {
            throw new InvalidArgumentException('Reinforcements require at least one unit.');
        }

        $options = (array) Arr::get($attributes, 'options', []);
        $departAt = $this->resolveDateTime(Arr::get($attributes, 'depart_at'));
        $speed = $this->resolveMovementSpeed($attributes, $units);
        $speedMultiplier = $this->resolveSpeedMultiplier($attributes);

        $timing = $this->calculateArrival($origin, $target, $speed, $departAt, $speedMultiplier);

        $movement = new MovementOrder([
            'user_id' => $user?->getKey(),
            'origin_village_id' => $origin->getKey(),
            'target_village_id' => $target->getKey(),
            'movement_type' => 'reinforcement',
            'status' => (string) Arr::get($attributes, 'status', 'en_route'),
            'depart_at' => $timing['depart_at'],
            'arrive_at' => $timing['arrive_at'],
            'payload' => [
                'units' => $units,
                'options' => $options,
                'travel_time' => $timing['travel_time'],
            ],
        ]);

        $movement->save();

        return $movement;
    }

    /**
     * Schedule a merchant shipment between two villages.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function sendTrade(array $attributes): MovementOrder
    {
        $origin = $this->resolveVillage(Arr::get($attributes, 'origin'), 'origin');
        $target = $this->resolveVillage(Arr::get($attributes, 'target'), 'target');
        $user = $this->resolveUser(Arr::get($attributes, 'user'), $origin, 'user');

        $resources = $this->normaliseResources(Arr::get($attributes, 'resources', []));

        if ($resources === []) {
            throw new InvalidArgumentException('A trade must contain at least one resource.');
        }

        $options = (array) Arr::get($attributes, 'options', []);
        $departAt = $this->resolveDateTime(Arr::get($attributes, 'depart_at'));
        $speed = $this->resolveTradeSpeed($attributes);
        $speedMultiplier = $this->resolveSpeedMultiplier($attributes);

        $timing = $this->calculateArrival($origin, $target, $speed, $departAt, $speedMultiplier);

        $movement = new MovementOrder([
            'user_id' => $user?->getKey(),
            'origin_village_id' => $origin->getKey(),
            'target_village_id' => $target->getKey(),
            'movement_type' => 'trade',
            'status' => (string) Arr::get($attributes, 'status', 'en_route'),
            'depart_at' => $timing['depart_at'],
            'arrive_at' => $timing['arrive_at'],
            'payload' => [
                'resources' => $resources,
                'merchants' => (int) Arr::get($attributes, 'merchants', 1),
                'capacity' => (int) Arr::get($attributes, 'capacity', 0),
                'options' => $options,
                'travel_time' => $timing['travel_time'],
            ],
        ]);

        $movement->save();

        return $movement;
    }

    /**
     * Calculate the arrival timing between two villages.
     */
    public function calculateArrival(
        Village|int $origin,
        Village|int $target,
        float $speed,
        CarbonInterface|string|int|null $departAt = null,
        float $speedMultiplier = 1.0,
    ): array {
        $originVillage = $this->resolveVillage($origin, 'origin');
        $targetVillage = $this->resolveVillage($target, 'target');

        if ($speed <= 0.0) {
            throw new InvalidArgumentException('Speed must be a positive value.');
        }

        if ($speedMultiplier <= 0.0) {
            throw new InvalidArgumentException('Speed multiplier must be a positive value.');
        }

        $departAtInstance = $this->resolveDateTime($departAt);

        $distance = $this->calculateDistance($originVillage, $targetVillage);
        $effectiveSpeed = $speed * $speedMultiplier;

        $travelSeconds = (int) round(($distance / $effectiveSpeed) * 3600);
        $travelSeconds = max(0, $travelSeconds);

        $arrival = (clone $departAtInstance)->addSeconds($travelSeconds);

        return [
            'distance' => $distance,
            'travel_time' => $travelSeconds,
            'depart_at' => $departAtInstance,
            'arrive_at' => $arrival,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, int>  $units
     */
    protected function resolveMovementSpeed(array $attributes, array $units): float
    {
        $explicit = Arr::get($attributes, 'speed');
        if ($explicit !== null) {
            $speed = (float) $explicit;
            if ($speed <= 0.0) {
                throw new InvalidArgumentException('Speed must be greater than zero.');
            }

            return $speed;
        }

        $unitSpeeds = Arr::get($attributes, 'unit_speeds', []);
        if (is_array($unitSpeeds) && $unitSpeeds !== []) {
            $slowest = null;

            foreach ($units as $unit => $quantity) {
                if ($quantity <= 0) {
                    continue;
                }

                if (array_key_exists($unit, $unitSpeeds)) {
                    $value = (float) $unitSpeeds[$unit];
                    $slowest = $slowest === null ? $value : min($slowest, $value);
                }
            }

            if ($slowest !== null) {
                if ($slowest <= 0.0) {
                    throw new InvalidArgumentException('Unit speeds must be greater than zero.');
                }

                return $slowest;
            }
        }

        $default = (float) Arr::get($attributes, 'default_speed', 1.0);

        if ($default <= 0.0) {
            throw new InvalidArgumentException('Default speed must be greater than zero.');
        }

        return $default;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function resolveTradeSpeed(array $attributes): float
    {
        $speed = (float) Arr::get($attributes, 'speed', (float) Arr::get($attributes, 'merchant_speed', 1.0));

        if ($speed <= 0.0) {
            throw new InvalidArgumentException('Merchant speed must be greater than zero.');
        }

        return $speed;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function resolveSpeedMultiplier(array $attributes): float
    {
        $multiplier = (float) Arr::get($attributes, 'speed_multiplier', (float) Arr::get($attributes, 'world_speed', 1.0));

        if ($multiplier <= 0.0) {
            throw new InvalidArgumentException('Speed multiplier must be greater than zero.');
        }

        return $multiplier;
    }

    protected function resolveMovementType(string $type): string
    {
        return match ($type) {
            'reinforcement' => 'reinforcement',
            'raid' => 'raid',
            'scout' => 'scout',
            default => 'attack',
        };
    }

    protected function calculateDistance(Village $origin, Village $target): float
    {
        $dx = ($origin->x ?? 0) - ($target->x ?? 0);
        $dy = ($origin->y ?? 0) - ($target->y ?? 0);

        return sqrt(($dx ** 2) + ($dy ** 2));
    }

    protected function resolveVillage(Village|int|array|null $value, string $label): Village
    {
        if ($value instanceof Village) {
            return $value;
        }

        if (is_array($value) && array_key_exists('id', $value)) {
            $value = $value['id'];
        }

        if (is_numeric($value)) {
            $model = Village::query()->find((int) $value);
            if ($model instanceof Village) {
                return $model;
            }

            throw (new ModelNotFoundException(sprintf('Village [%s] not found.', $value)))->setModel(Village::class, [(int) $value]);
        }

        throw new InvalidArgumentException(sprintf('Unable to resolve %s village.', $label));
    }

    protected function resolveUser(User|int|array|null $value, Village $fallback, string $label, bool $allowNull = false): ?User
    {
        if ($value instanceof User) {
            return $value;
        }

        if ($value === null) {
            $owner = $fallback->owner;
            if ($owner instanceof User) {
                return $owner;
            }

            if ($allowNull) {
                return null;
            }

            throw new InvalidArgumentException(sprintf('No %s user was provided and the village has no owner.', $label));
        }

        if (is_array($value) && array_key_exists('id', $value)) {
            $value = $value['id'];
        }

        if (is_numeric($value)) {
            $model = User::query()->find((int) $value);
            if ($model instanceof User) {
                return $model;
            }

            throw (new ModelNotFoundException(sprintf('User [%s] not found.', $value)))->setModel(User::class, [(int) $value]);
        }

        throw new InvalidArgumentException(sprintf('Unable to resolve %s user.', $label));
    }

    /**
     * @param  array<string, mixed>  $units
     * @return array<string, int>
     */
    protected function normaliseUnits(array $units): array
    {
        $normalised = [];

        foreach ($units as $key => $quantity) {
            $amount = (int) $quantity;

            if ($amount < 0) {
                throw new InvalidArgumentException('Unit quantities cannot be negative.');
            }

            if ($amount === 0) {
                continue;
            }

            $normalised[(string) $key] = $amount;
        }

        return $normalised;
    }

    /**
     * @param  array<string, mixed>  $resources
     * @return array<string, int>
     */
    protected function normaliseResources(array $resources): array
    {
        $keys = ['wood', 'clay', 'iron', 'crop'];
        $normalised = [];

        foreach ($keys as $resource) {
            $value = (int) Arr::get($resources, $resource, 0);

            if ($value < 0) {
                throw new InvalidArgumentException('Resource quantities cannot be negative.');
            }

            if ($value > 0) {
                $normalised[$resource] = $value;
            }
        }

        return $normalised;
    }

    protected function resolveDateTime(CarbonInterface|string|int|null $value): CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value->copy();
        }

        if ($value === null) {
            return Carbon::now();
        }

        if (is_int($value)) {
            return Carbon::createFromTimestamp($value);
        }

        if (is_string($value)) {
            return Carbon::parse($value);
        }

        throw new InvalidArgumentException('Unable to resolve datetime value.');
    }
}
