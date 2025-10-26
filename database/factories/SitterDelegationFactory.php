<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SitterPermission;
use App\Models\SitterDelegation;
use App\Models\User;
use App\ValueObjects\SitterPermissionSet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<SitterDelegation>
 */
class SitterDelegationFactory extends Factory
{
    protected $model = SitterDelegation::class;

    public function definition(): array
    {
        $permissionKeys = collect(fake()->randomElements(SitterPermission::cases(), fake()->numberBetween(2, 5)))
            ->map(static fn (SitterPermission $permission): string => $permission->key())
            ->all();

        $expiresAt = fake()->boolean(40)
            ? Carbon::now()->addDays(fake()->numberBetween(7, 45))
            : null;

        return [
            'owner_user_id' => User::factory(),
            'sitter_user_id' => User::factory(),
            'permissions' => SitterPermissionSet::fromArray($permissionKeys),
            'expires_at' => $expiresAt,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => Carbon::now()->subDays(fake()->numberBetween(1, 14)),
        ]);
    }
}
