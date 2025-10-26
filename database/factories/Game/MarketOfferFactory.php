<?php

declare(strict_types=1);

namespace Database\Factories\Game;

use App\Models\Game\MarketOffer;
use App\Models\Game\Village;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketOffer>
 */
class MarketOfferFactory extends Factory
{
    protected $model = MarketOffer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $give = [
            'wood' => $this->faker->numberBetween(200, 600),
            'clay' => 0,
            'iron' => 0,
            'crop' => 0,
        ];

        $want = [
            'wood' => 0,
            'clay' => $this->faker->numberBetween(180, 650),
            'iron' => 0,
            'crop' => 0,
        ];

        $capacity = 500;
        $merchants = (int) max(1, ceil(array_sum($give) / $capacity));

        return [
            'village_id' => Village::factory(),
            'give' => $give,
            'want' => $want,
            'merchants' => $merchants,
        ];
    }
}
