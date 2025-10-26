<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<LoginActivity>
 */
class LoginActivityFactory extends Factory
{
    protected $model = LoginActivity::class;

    public function definition(): array
    {
        $ip = fake()->ipv4();

        return [
            'user_id' => User::factory(),
            'acting_sitter_id' => null,
            'ip_address' => $ip,
            'ip_address_hash' => hash('sha256', $ip),
            'user_agent' => fake()->userAgent(),
            'world_id' => 'world-1',
            'device_hash' => hash('sha256', $ip.'-'.Str::random(10)),
            'geo' => [
                'country' => fake()->countryCode(),
                'city' => fake()->city(),
            ],
            'logged_at' => Carbon::now()->subMinutes(fake()->numberBetween(5, 240)),
            'via_sitter' => false,
        ];
    }
}
