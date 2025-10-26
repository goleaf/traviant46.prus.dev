<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AuthEventType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\AuthEvent>
 */
class AuthEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $event = $this->faker->randomElement(AuthEventType::cases());

        return [
            'user_id' => User::factory(),
            'event_type' => $event->value,
            'identifier' => $this->faker->safeEmail(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'meta' => [
                'status' => 'queued',
            ],
            'occurred_at' => now(),
        ];
    }
}
