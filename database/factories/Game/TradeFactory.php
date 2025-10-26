<?php

declare(strict_types=1);

namespace Database\Factories\Game;

use App\Models\Game\Trade;
use App\Models\Game\Village;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Trade>
 */
class TradeFactory extends Factory
{
    protected $model = Trade::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $resources = [
            'wood' => $this->faker->numberBetween(150, 600),
            'clay' => $this->faker->numberBetween(75, 240),
            'iron' => $this->faker->numberBetween(50, 220),
            'crop' => 0,
        ];

        $merchants = (int) max(1, ceil(array_sum($resources) / 500));

        return [
            'origin' => Village::factory(),
            'target' => Village::factory(),
            'payload' => [
                'resources' => $resources,
                'merchants' => $merchants,
                'context' => 'direct',
            ],
            'eta' => Carbon::now()->addMinutes($this->faker->numberBetween(20, 180)),
        ];
    }
}
