<?php

declare(strict_types=1);

namespace App\Services\Game;

use App\Models\Game\MarketOffer;
use App\Models\Game\Trade;
use App\Models\Game\Village;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use RuntimeException;

class MarketService
{
    /**
     * @var array<int, array{base: int, capacity: int, speed: int}>
     */
    private const MERCHANT_PROFILES = [
        1 => ['base' => 3, 'capacity' => 500, 'speed' => 16],
        2 => ['base' => 2, 'capacity' => 1000, 'speed' => 12],
        3 => ['base' => 3, 'capacity' => 750, 'speed' => 24],
        6 => ['base' => 5, 'capacity' => 750, 'speed' => 16],
        7 => ['base' => 4, 'capacity' => 500, 'speed' => 20],
    ];

    /**
     * @var array<int, string>
     */
    private const RESOURCE_KEYS = ['wood', 'clay', 'iron', 'crop'];

    private DatabaseManager $database;

    public function __construct(DatabaseManager $database)
    {
        $this->database = $database;
    }

    /**
     * @return array{
     *     total: int,
     *     available: int,
     *     in_use: int,
     *     speed: float,
     *     capacity_per_merchant: int,
     *     marketplace_level: int,
     *     trade_office_level: int,
     *     tribe_id: int
     * }
     */
    public function summarizeMerchants(Village $village): array
    {
        $village->loadMissing(['owner', 'world', 'marketOffers', 'outgoingTrades']);

        $profile = $this->profileForVillage($village);
        $worldSpeed = max((float) ($village->world?->speed ?? 1.0), 1.0);

        $marketplaceLevel = $this->buildingLevel($village, 17);
        $tradeOfficeLevel = $this->buildingLevel($village, 28);

        $capacityPerMerchant = (int) round(
            $profile['capacity'] * $worldSpeed * (1 + ($tradeOfficeLevel * 0.10)),
        );

        $travelSpeed = (float) ($profile['speed'] * $worldSpeed);

        $total = (int) ($profile['base'] + $marketplaceLevel);

        $reservedFromOffers = (int) $village->marketOffers->sum('merchants');

        $activeTrades = $village->outgoingTrades
            ->filter(static fn (Trade $trade): bool => $trade->eta?->isFuture() ?? false)
            ->sum(static fn (Trade $trade): int => (int) data_get($trade->payload, 'merchants', 0));

        $inUse = min($total, $reservedFromOffers + (int) $activeTrades);
        $available = max($total - $inUse, 0);

        return [
            'total' => $total,
            'available' => $available,
            'in_use' => $inUse,
            'speed' => $travelSpeed,
            'capacity_per_merchant' => max($capacityPerMerchant, 1),
            'marketplace_level' => $marketplaceLevel,
            'trade_office_level' => $tradeOfficeLevel,
            'tribe_id' => $this->tribeForVillage($village),
        ];
    }

    /**
     * @param array<string, int|string|null> $give
     * @param array<string, int|string|null> $want
     */
    public function createOffer(Village $village, array $give, array $want): MarketOffer
    {
        $normalizedGive = $this->normaliseResources($give);
        $normalizedWant = $this->normaliseResources($want);

        if ($this->isZeroBundle($normalizedGive)) {
            throw new RuntimeException('Offer must contain at least one resource to give.');
        }

        if ($this->isZeroBundle($normalizedWant)) {
            throw new RuntimeException('Offer must request at least one resource in return.');
        }

        return $this->database->transaction(function () use ($village, $normalizedGive, $normalizedWant): MarketOffer {
            $summary = $this->summarizeMerchants($village->fresh());
            $merchantsNeeded = $this->requiredMerchants($normalizedGive, $summary['capacity_per_merchant']);

            if ($merchantsNeeded > $summary['available']) {
                throw new RuntimeException('Not enough merchants are idle to post this offer.');
            }

            $this->deductResources($village, $normalizedGive);

            return $village->marketOffers()->create([
                'give' => $normalizedGive,
                'want' => $normalizedWant,
                'merchants' => $merchantsNeeded,
            ]);
        });
    }

    public function cancelOffer(MarketOffer $offer): void
    {
        $offer->loadMissing('village');

        $this->database->transaction(function () use ($offer): void {
            $village = $offer->village;

            if ($village !== null) {
                $this->addResources($village, $this->normaliseResources($offer->give ?? []));
            }

            $offer->delete();
        });
    }

    /**
     * @return array{give: Trade, want: ?Trade}
     */
    public function acceptOffer(MarketOffer $offer, Village $acceptingVillage): array
    {
        $offer->loadMissing('village');

        $origin = $offer->village;

        if (! $origin instanceof Village) {
            throw new RuntimeException('Offer origin could not be determined.');
        }

        return $this->database->transaction(function () use ($offer, $origin, $acceptingVillage): array {
            $originSummary = $this->summarizeMerchants($origin->fresh());
            $acceptorSummary = $this->summarizeMerchants($acceptingVillage->fresh());

            $giveBundle = $this->normaliseResources($offer->give ?? []);
            $wantBundle = $this->normaliseResources($offer->want ?? []);

            $acceptorMerchants = $this->requiredMerchants($wantBundle, $acceptorSummary['capacity_per_merchant']);

            if ($acceptorMerchants > $acceptorSummary['available']) {
                throw new RuntimeException('Accepting village does not have enough idle merchants.');
            }

            $this->deductResources($acceptingVillage, $wantBundle);

            $giveTrade = $this->dispatchTrade(
                $origin,
                $acceptingVillage,
                $giveBundle,
                $offer->merchants,
                $originSummary['speed'],
                [
                    'context' => 'offer:give',
                    'offer_id' => $offer->getKey(),
                ],
            );

            $wantTrade = null;

            if (! $this->isZeroBundle($wantBundle)) {
                $wantTrade = $this->dispatchTrade(
                    $acceptingVillage,
                    $origin,
                    $wantBundle,
                    max($acceptorMerchants, 1),
                    $acceptorSummary['speed'],
                    [
                        'context' => 'offer:want',
                        'offer_id' => $offer->getKey(),
                    ],
                );
            }

            $offer->delete();

            return [
                'give' => $giveTrade,
                'want' => $wantTrade,
            ];
        });
    }

    /**
     * @param array<string, int|string|null> $payload
     */
    public function sendTrade(Village $origin, Village $target, array $payload): Trade
    {
        $resources = $this->normaliseResources($payload);

        if ($this->isZeroBundle($resources)) {
            throw new RuntimeException('Trade payload must contain at least one resource.');
        }

        if ($origin->is($target)) {
            throw new RuntimeException('Cannot dispatch merchants to the same village.');
        }

        return $this->database->transaction(function () use ($origin, $target, $resources): Trade {
            $originSummary = $this->summarizeMerchants($origin->fresh());
            $merchantsNeeded = $this->requiredMerchants($resources, $originSummary['capacity_per_merchant']);

            if ($merchantsNeeded > $originSummary['available']) {
                throw new RuntimeException('Not enough merchants are idle to send this trade.');
            }

            $this->deductResources($origin, $resources);

            return $this->dispatchTrade(
                $origin,
                $target,
                $resources,
                max($merchantsNeeded, 1),
                $originSummary['speed'],
                [
                    'context' => 'direct',
                ],
            );
        });
    }

    /**
     * @param array<string, int> $resources
     * @param array<string, mixed> $extraPayload
     */
    private function dispatchTrade(
        Village $origin,
        Village $target,
        array $resources,
        int $merchants,
        float $speed,
        array $extraPayload = []
    ): Trade {
        $eta = $this->calculateEta($origin, $target, $speed);

        return Trade::query()->create([
            'origin' => $origin->getKey(),
            'target' => $target->getKey(),
            'payload' => array_merge($extraPayload, [
                'resources' => $resources,
                'merchants' => $merchants,
            ]),
            'eta' => $eta,
        ]);
    }

    private function buildingLevel(Village $village, int $gid): int
    {
        $village->loadMissing('buildings');

        $building = $village->buildings
            ->firstWhere(static fn ($building) => (int) ($building->building_type ?? 0) === $gid);

        return (int) ($building->level ?? 0);
    }

    /**
     * @param array<string, int|string|null> $resources
     * @return array<string, int>
     */
    private function normaliseResources(array $resources): array
    {
        $normalized = array_fill_keys(self::RESOURCE_KEYS, 0);

        foreach ($resources as $resource => $value) {
            if (! in_array($resource, self::RESOURCE_KEYS, true)) {
                continue;
            }

            $normalized[$resource] = max(0, (int) $value);
        }

        return $normalized;
    }

    /**
     * @param array<string, int> $resources
     */
    private function isZeroBundle(array $resources): bool
    {
        return array_sum($resources) === 0;
    }

    /**
     * @param array<string, int> $resources
     */
    private function requiredMerchants(array $resources, int $capacityPerMerchant): int
    {
        $total = array_sum($resources);

        if ($total === 0) {
            return 0;
        }

        return (int) max(1, ceil($total / max(1, $capacityPerMerchant)));
    }

    /**
     * @param array<string, int> $resources
     */
    private function deductResources(Village $village, array $resources): void
    {
        $balances = $this->resourceBalances($village);

        foreach ($resources as $resource => $amount) {
            if ($amount <= 0) {
                continue;
            }

            if ($balances[$resource] < $amount) {
                throw new RuntimeException(__('Not enough :resource in storage.', ['resource' => $resource]));
            }

            $balances[$resource] -= $amount;
        }

        $this->persistResourceBalances($village, $balances);
    }

    /**
     * @param array<string, int> $resources
     */
    private function addResources(Village $village, array $resources): void
    {
        $balances = $this->resourceBalances($village);

        foreach ($resources as $resource => $amount) {
            if ($amount <= 0) {
                continue;
            }

            $balances[$resource] += $amount;
        }

        $this->persistResourceBalances($village, $balances);
    }

    /**
     * @return array<string, int>
     */
    private function resourceBalances(Village $village): array
    {
        $source = Arr::wrap($village->resource_balances ?? []);
        $balances = array_fill_keys(self::RESOURCE_KEYS, 0);

        foreach ($source as $resource => $value) {
            if (! in_array($resource, self::RESOURCE_KEYS, true)) {
                continue;
            }

            $balances[$resource] = (int) max(0, (int) $value);
        }

        return $balances;
    }

    /**
     * @param array<string, int> $balances
     */
    private function persistResourceBalances(Village $village, array $balances): void
    {
        $village->forceFill([
            'resource_balances' => $balances,
        ])->save();

        $village->refresh();
    }

    private function profileForVillage(Village $village): array
    {
        $tribeId = $this->tribeForVillage($village);

        return self::MERCHANT_PROFILES[$tribeId] ?? self::MERCHANT_PROFILES[1];
    }

    private function tribeForVillage(Village $village): int
    {
        $tribe = $village->owner?->tribe ?? $village->owner?->race ?? 1;

        $tribeId = is_numeric($tribe) ? (int) $tribe : null;

        if ($tribeId !== null && isset(self::MERCHANT_PROFILES[$tribeId])) {
            return $tribeId;
        }

        return 1;
    }

    private function calculateEta(Village $origin, Village $target, float $speedFieldsPerHour): Carbon
    {
        $distance = $this->distanceBetween($origin, $target);
        $speed = max($speedFieldsPerHour, 1.0);

        $hours = $distance / $speed;
        $seconds = (int) max(60, ceil($hours * 3600));

        return Carbon::now()->addSeconds($seconds);
    }

    private function distanceBetween(Village $origin, Village $target): float
    {
        $dx = (int) $origin->x_coordinate - (int) $target->x_coordinate;
        $dy = (int) $origin->y_coordinate - (int) $target->y_coordinate;

        return sqrt(($dx ** 2) + ($dy ** 2));
    }
}
