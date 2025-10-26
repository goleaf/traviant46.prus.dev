<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'legacy_uid' => $this->generateLegacyUid(),
            'username' => Str::lower(Str::replace(' ', '', fake()->unique()->userName())).fake()->unique()->numberBetween(1, 99),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Str0ng!Pass1234'),
            'role' => StaffRole::Player,
            'race' => fake()->randomElement([1, 2, 3, 6, 7]),
            'tribe' => null,
            'remember_token' => Str::random(10),
            'is_banned' => false,
            'ban_reason' => null,
            'ban_issued_at' => null,
            'ban_expires_at' => null,
            'beginner_protection_until' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    private function generateLegacyUid(): int
    {
        do {
            $legacyUid = fake()->unique()->numberBetween(User::FIRST_PLAYER_LEGACY_UID, 50000);
        } while (User::isReservedLegacyUid($legacyUid));

        return $legacyUid;
    }
}
