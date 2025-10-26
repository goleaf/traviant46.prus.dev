<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    if (! app()->bound('session')) {
        config()->set('session.driver', 'array');
        app()->register(\Illuminate\Session\SessionServiceProvider::class);
    }

    if (! session()->isStarted()) {
        session()->start();
    }

    if (! app()->bound('bugsnag')) {
        app()->singleton('bugsnag', fn () => new class
        {
            public function __call(string $name, array $arguments): void
            {
                // silent stub for test environment
            }
        });
    }
});

it('allows the legacy admin account to view the dashboard', function (): void {
    $admin = User::factory()->create([
        'legacy_uid' => User::LEGACY_ADMIN_UID,
        'email' => 'command@travian.dev',
    ]);

    actingAs($admin);
    actingAs($admin, 'admin');

    $response = $this->get(route('admin.dashboard'));

    $response->assertSuccessful();
    $response->assertSee('Administration command center');
});

it('rejects regular players from the admin dashboard', function (): void {
    $player = User::factory()->create();

    actingAs($player);

    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});
