<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MultiAccountAlertSeverity;
use App\Enums\MultiAccountAlertStatus;
use App\Models\MultiAccountAlert;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<MultiAccountAlert>
 */
class MultiAccountAlertFactory extends Factory
{
    protected $model = MultiAccountAlert::class;

    public function definition(): array
    {
        $firstSeen = Carbon::now()->subHours(fake()->numberBetween(2, 72));
        $lastSeen = (clone $firstSeen)->addMinutes(fake()->numberBetween(5, 180));

        return [
            'alert_id' => (string) Str::uuid(),
            'group_key' => Str::uuid()->toString(),
            'source_type' => fake()->randomElement(['ip', 'device']),
            'world_id' => 'world-1',
            'ip_address' => fake()->ipv4(),
            'device_hash' => hash('sha256', Str::random(32)),
            'ip_address_hash' => hash('sha256', Str::random(16)),
            'user_ids' => [],
            'occurrences' => fake()->numberBetween(1, 12),
            'first_seen_at' => $firstSeen,
            'window_started_at' => $firstSeen->copy()->subMinutes(30),
            'last_seen_at' => $lastSeen,
            'severity' => MultiAccountAlertSeverity::Medium,
            'status' => MultiAccountAlertStatus::Open,
            'suppression_reason' => null,
            'resolved_at' => null,
            'resolved_by_user_id' => null,
            'dismissed_at' => null,
            'dismissed_by_user_id' => null,
            'notes' => null,
            'metadata' => [
                'reason' => 'shared-network',
            ],
            'last_notified_at' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(function () {
            $resolvedAt = Carbon::now()->subHours(fake()->numberBetween(1, 24));

            return [
                'status' => MultiAccountAlertStatus::Resolved,
                'resolved_at' => $resolvedAt,
                'last_seen_at' => $resolvedAt,
            ];
        });
    }

    public function dismissed(): static
    {
        return $this->state(function () {
            $dismissedAt = Carbon::now()->subHours(fake()->numberBetween(1, 48));

            return [
                'status' => MultiAccountAlertStatus::Dismissed,
                'dismissed_at' => $dismissedAt,
                'last_seen_at' => $dismissedAt,
            ];
        });
    }
}
