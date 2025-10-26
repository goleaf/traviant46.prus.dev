<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LegacyRoleGuardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_guard_requires_legacy_uid_zero(): void
    {
        $password = 'Admin#1234';

        $admin = User::factory()->create([
            'legacy_uid' => User::LEGACY_ADMIN_UID,
            'email' => 'admin-guard@example.com',
            'password' => Hash::make($password),
        ]);

        $regularUser = User::factory()->create([
            'email' => 'regular@example.com',
            'password' => Hash::make($password),
        ]);

        $this->assertTrue(Auth::guard('admin')->validate([
            'email' => $admin->email,
            'password' => $password,
        ]));

        $this->assertFalse(Auth::guard('admin')->validate([
            'email' => $regularUser->email,
            'password' => $password,
        ]));
    }

    public function test_multihunter_guard_requires_legacy_uid_two(): void
    {
        $password = 'Multi#1234';

        $multihunter = User::factory()->create([
            'legacy_uid' => User::LEGACY_MULTIHUNTER_UID,
            'email' => 'multihunter-guard@example.com',
            'password' => Hash::make($password),
        ]);

        $regularUser = User::factory()->create([
            'email' => 'regular-mh@example.com',
            'password' => Hash::make($password),
        ]);

        $this->assertTrue(Auth::guard('multihunter')->validate([
            'email' => $multihunter->email,
            'password' => $password,
        ]));

        $this->assertFalse(Auth::guard('multihunter')->validate([
            'email' => $regularUser->email,
            'password' => $password,
        ]));
    }
}
