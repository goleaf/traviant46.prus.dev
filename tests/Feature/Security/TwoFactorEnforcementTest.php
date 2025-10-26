<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Fortify;
use Tests\TestCase;

class TwoFactorEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_without_two_factor_is_redirected_to_security_setup(): void
    {
        $user = User::factory()->create([
            'role' => StaffRole::Admin,
            'legacy_uid' => User::LEGACY_ADMIN_UID,
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertRedirect(route('security.two-factor'));
        $response->assertSessionHas('status', 'two-factor-required');
    }

    public function test_staff_with_confirmed_two_factor_can_access_admin_routes(): void
    {
        $secret = Fortify::currentEncrypter()->encrypt('test-secret');
        $recoveryCodes = Fortify::currentEncrypter()->encrypt(json_encode(['code-one', 'code-two']));

        $user = User::factory()->create([
            'role' => StaffRole::Admin,
            'legacy_uid' => User::LEGACY_ADMIN_UID,
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $recoveryCodes,
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertSuccessful();
    }

    public function test_regular_players_are_not_prompted_for_two_factor(): void
    {
        $user = User::factory()->create([
            'role' => StaffRole::Player,
            'two_factor_secret' => null,
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertSuccessful();
    }
}
