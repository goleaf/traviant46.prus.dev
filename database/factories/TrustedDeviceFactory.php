<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrustedDevice>
 */
class TrustedDeviceFactory extends Factory
{
    protected $model = TrustedDevice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstTrustedAt = Carbon::now()->subDays(fake()->numberBetween(0, 30));
        $lastUsedAt = (clone $firstTrustedAt)->addHours(fake()->numberBetween(1, 120));
        $expiresAt = fake()->boolean(60)
            ? (clone $firstTrustedAt)->addDays(fake()->numberBetween(30, 240))
            : null;

        return [
            'user_id' => User::factory(),
            'public_id' => (string) Str::uuid(),
            'label' => fake()->optional(0.5)->words(2, true),
            'token_hash' => hash('sha512', Str::random(64)),
            'fingerprint_hash' => fake()->optional(0.7)->sha1(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'first_trusted_at' => $firstTrustedAt,
            'last_used_at' => $lastUsedAt,
            'expires_at' => $expiresAt,
            'revoked_at' => null,
            'metadata' => array_filter([
                'accept_language' => fake()->optional()->languageCode(),
                'sec_ch_ua_platform' => fake()->optional()->randomElement(['"Windows"', '"macOS"', '"Linux"', '"Android"']),
            ]),
        ];
    }
}
