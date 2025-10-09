<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SitterSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitter_login_tracks_context_and_activity(): void
    {
        $ownerPassword = 'Owner#5678';
        $sitterPassword = 'Sitter#5678';

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
        $password = 'Reset#1234';
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
}
