<?php

namespace Tests\Feature\Auth;

use App\Enums\SitterPermission;
use App\Models\User;
use App\Services\Auth\SessionContextManager;
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

        $context = app(SessionContextManager::class);
        $this->assertTrue($context->actingAsSitter());
        $this->assertSame($sitter->getKey(), $context->sitterId());
        $this->assertSame($owner->getKey(), $context->controllingAccountId());
        $this->assertContains(SitterPermission::VIEW_VILLAGE, $context->sitterPermissions());

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

        $sitter = User::factory()->create([
            'username' => 'session-sitter',
            'email' => 'session-sitter@example.com',
            'password' => Hash::make('Session#1234'),
        ]);

        app(SessionContextManager::class)->enterSitterContext($owner, $sitter);

        $response = $this->post('/login', [
            'login' => 'reset-owner',
            'password' => $password,
        ]);

        $response->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($owner);

        $context = app(SessionContextManager::class);
        $this->assertFalse($context->actingAsSitter());
        $this->assertNull($context->sitterId());
    }
}
