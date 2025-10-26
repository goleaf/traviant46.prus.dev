<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\SitterPermissionPreset;
use App\Models\SitterDelegation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SitterSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitter_login_tracks_context_and_activity(): void
    {
        $ownerPassword = 'Owner#5678PQ';
        $sitterPassword = 'Sitter#5678RS';

        $owner = User::factory()->create([
            'username' => 'primary-owner',
            'email' => 'primary@example.com',
            'password' => Hash::make($ownerPassword),
        ]);

        $sitter = User::factory()->create([
            'username' => 'delegated-sitter',
            'email' => 'sitter@example.com',
            'password' => Hash::make($sitterPassword),
        ]);

        $owner->forceFill(['sit1_uid' => $sitter->getKey()])->save();

        $response = $this->post('/login', [
            'login' => 'primary-owner',
            'password' => $sitterPassword,
        ]);

        $response->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($owner);
        $this->assertTrue(session()->get('auth.acting_as_sitter'));
        $this->assertSame($sitter->getKey(), session()->get('auth.sitter_id'));

        $this->assertDatabaseHas('login_activities', [
            'user_id' => $owner->getKey(),
            'acting_sitter_id' => $sitter->getKey(),
            'via_sitter' => true,
        ]);
    }

    public function test_owner_login_resets_sitter_context(): void
    {
        $password = 'Reset#1234TU';
        $owner = User::factory()->create([
            'username' => 'reset-owner',
            'email' => 'reset@example.com',
            'password' => Hash::make($password),
        ]);

        session()->put('auth.acting_as_sitter', true);
        session()->put('auth.sitter_id', 999);

        $response = $this->post('/login', [
            'login' => 'reset-owner',
            'password' => $password,
        ]);

        $response->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($owner);
        $this->assertFalse(session()->get('auth.acting_as_sitter', false));
        $this->assertNull(session()->get('auth.sitter_id'));
    }

    public function test_sitter_delegation_banner_is_visible_on_dashboard(): void
    {
        $ownerPassword = 'Owner#9999';
        $sitterPassword = 'Sitter#9999';

        $owner = User::factory()->create([
            'username' => 'delegation-owner',
            'email' => 'delegation-owner@example.com',
            'password' => Hash::make($ownerPassword),
        ]);

        $sitter = User::factory()->create([
            'username' => 'delegation-sitter',
            'email' => 'delegation-sitter@example.com',
            'password' => Hash::make($sitterPassword),
        ]);

        SitterDelegation::query()->create([
            'owner_user_id' => $owner->getKey(),
            'sitter_user_id' => $sitter->getKey(),
            'permissions' => SitterPermissionPreset::Guardian->permissionKeys(),
            'created_by' => $owner->getKey(),
            'updated_by' => $owner->getKey(),
        ]);

        $this->post('/login', [
            'login' => $owner->username,
            'password' => $sitterPassword,
        ])->assertRedirect(route('home'));

        $response = $this->get('/home');

        $response->assertOk()
            ->assertSee('Delegation context')
            ->assertSee('delegation-sitter')
            ->assertSee('delegation-owner')
            ->assertSee('Guardian');
    }
}
