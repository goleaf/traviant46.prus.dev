<?php

namespace App\Services\Game;

use App\Contracts\Game\HeroItemRepository;
use App\ValueObjects\Game\Hero\HeroItem;
use InvalidArgumentException;

class HeroHelperService
{
    public function __construct(
        private readonly HeroItemRepository $items,
        private int $gameSpeed = 1,
        private float $movementSpeedRate = 1.0,
    ) {
    }

    public function setGameSpeed(int $speed): void
    {
        $this->gameSpeed = max(1, $speed);
    }

    public function setMovementSpeedRate(float $rate): void
    {
        $this->movementSpeedRate = max(0.1, $rate);
    }

    public function calculateTotalPower(int $race, int $points, HeroItem|array|int|null $rightHand, HeroItem|array|int|null $leftHand, HeroItem|array|int|null $body): float
    {
        return $this->calculatePower($race, $points) + $this->calculateItemPower($rightHand, $leftHand, $body);
    }

    public function calculatePower(int $race, int $points): float
    {
        return 100 + $points * ($race === 1 ? 100 : 80);
    }

    public function calculateItemPower(HeroItem|array|int|null $rightHand, HeroItem|array|int|null $leftHand, HeroItem|array|int|null $body): float
    {
        $rightHandItem = $this->resolveItem($rightHand);
        $leftHandItem = $this->resolveItem($leftHand);
        $bodyItem = $this->resolveItem($body);

        $total = 0;
        if ($rightHandItem && $this->inRange($rightHandItem->type, 16, 60, 115, 144)) {
            $total += $rightHandItem->get('hero_power');
        }
        if ($leftHandItem && in_array($leftHandItem->type, [76, 77, 78], true)) {
            $total += $leftHandItem->get('hero_power');
        }
        if ($bodyItem && $bodyItem->type >= 88 && $bodyItem->type <= 93) {
            $total += $bodyItem->get('hero_power');
        }

        return $total;
    }

    public function calculateOffBonus(int $points): float
    {
        return $points * 0.2;
    }

    public function calculateDefBonus(int $points): float
    {
        return $points * 0.2;
    }

    public function calculateTotalHealth(HeroItem|array|int|null $helmet, HeroItem|array|int|null $body, HeroItem|array|int|null $shoes): float
    {
        return $this->calculateHealth() + $this->calculateItemHealth($helmet, $body, $shoes);
    }

    public function calculateHealth(): float
    {
        if ($this->gameSpeed <= 10) {
            return 10 + (5 * ($this->gameSpeed - 1));
        }

        return 10 * min((int) ceil($this->gameSpeed / 250), 50);
    }

    public function calculateItemHealth(HeroItem|array|int|null $helmet, HeroItem|array|int|null $body, HeroItem|array|int|null $shoes): float
    {
        $helmetItem = $this->resolveItem($helmet);
        $bodyItem = $this->resolveItem($body);
        $shoeItem = $this->resolveItem($shoes);

        $total = 0;
        if ($helmetItem && in_array($helmetItem->type, [4, 5, 6], true)) {
            $total += $helmetItem->get('reg');
        }
        if ($bodyItem && in_array($bodyItem->type, [82, 83, 84, 85, 86, 87], true)) {
            $total += $bodyItem->get('reg');
        }
        if ($shoeItem && in_array($shoeItem->type, [94, 95, 96], true)) {
            $total += $shoeItem->get('reg');
        }

        return $total;
    }

    public function calculateResist(HeroItem|array|int|null $body): float
    {
        $item = $this->resolveItem($body);
        if ($item && in_array($item->type, [84, 85, 86, 87, 91, 92, 93], true)) {
            return $item->get('resist');
        }

        return 0;
    }

    /**
     * @return array{num: int, eff: float}
     */
    public function getBandages(HeroItem|array|int|null $bag): array
    {
        $item = $this->resolveItem($bag);
        if ($item && in_array($item->category, [7, 8], true)) {
            return [
                'num' => (int) round($item->get('num')),
                'eff' => $item->get('revive'),
            ];
        }

        if (is_array($bag) && isset($bag['num'])) {
            return ['num' => (int) $bag['num'], 'eff' => 0];
        }

        return ['num' => 0, 'eff' => 0];
    }

    public function calculateRobPoints(HeroItem|array|int|null $leftHand): float
    {
        $item = $this->resolveItem($leftHand);
        if ($item && in_array($item->type, [73, 74, 75], true)) {
            return $item->get('raid');
        }

        return 0;
    }

    public function getCages(HeroItem|array|int|null $bag): int
    {
        $item = $this->resolveItem($bag);
        if ($item && $item->category === 9) {
            return (int) round($item->get('num'));
        }

        if (is_array($bag) && isset($bag['num'])) {
            return (int) $bag['num'];
        }

        return 0;
    }

    public function calculateTotalSpeed(int $race, HeroItem|array|int|null $horse, HeroItem|array|int|null $shoes, bool $cavalryOnly = false): float
    {
        return $this->calculateSpeed($race, $horse, $cavalryOnly) + $this->calculateItemSpeed($horse, $shoes);
    }

    public function calculateSpeed(int $race, HeroItem|array|int|null $horse, bool $cavalryOnly = false): float
    {
        $horseItem = $this->resolveItem($horse);
        $increase = ($race === 7 && $horseItem && $cavalryOnly) ? 3 : 0;
        $isCavalry = $horseItem && in_array($horseItem->type, [103, 104, 105], true);

        $base = ($race === 3 ? 7 + ($isCavalry ? 5 : 0) : 7) * $this->movementSpeedRate;

        if ($isCavalry) {
            return $base + $horseItem->get('speed_horse') + $increase;
        }

        return $base + $increase;
    }

    public function calculateItemSpeed(HeroItem|array|int|null $horse, HeroItem|array|int|null $shoes): float
    {
        $horseItem = $this->resolveItem($horse);
        $shoeItem = $this->resolveItem($shoes);

        if ($horseItem && in_array($horseItem->type, [103, 104, 105], true)) {
            if ($shoeItem && in_array($shoeItem->type, [100, 101, 102], true)) {
                return $shoeItem->get('hero_cav_speed');
            }
        }

        return 0;
    }

    /**
     * @return array{0: float, 1: float}
     */
    public function calculateTrainEffect(HeroItem|array|int|null $helmet): array
    {
        $helmetItem = $this->resolveItem($helmet);
        $effect = [0.0, 0.0];

        if ($helmetItem && in_array($helmetItem->type, [13, 14, 15], true)) {
            $effect[0] += $helmetItem->get('inf');
            $effect[1] += $helmetItem->get('cav');
        }

        return $effect;
    }

    private function inRange(int $value, int $startA, int $endA, int $startB, int $endB): bool
    {
        return ($value >= $startA && $value <= $endA) || ($value >= $startB && $value <= $endB);
    }

    private function resolveItem(HeroItem|array|int|null $reference): ?HeroItem
    {
        if ($reference instanceof HeroItem) {
            return $reference;
        }

        if ($reference === null) {
            return null;
        }

        if (is_int($reference)) {
            if ($reference === 0) {
                return null;
            }

            return $this->items->find($reference);
        }

        if (is_array($reference)) {
            if (($reference['id'] ?? 0) === 0 && ($reference['type'] ?? 0) === 0) {
                return null;
            }

            return new HeroItem(
                id: (int) ($reference['id'] ?? $reference['type'] ?? 0),
                type: (int) ($reference['type'] ?? 0),
                category: (int) ($reference['btype'] ?? $reference['category'] ?? 0),
                attributes: $reference,
            );
        }

        throw new InvalidArgumentException('Unsupported hero item reference type.');
    }
}
