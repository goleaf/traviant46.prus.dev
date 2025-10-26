<?php

declare(strict_types=1);

use App\Livewire\Admin\Dashboard;
use App\Models\Impersonation;
use App\Models\User;
use App\Services\Auth\ImpersonationManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;

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
                // ignore Bugsnag calls during tests
            }
        });
    }
});

it('allows the admin dashboard to trigger impersonation sessions', function (): void {
    $admin = User::factory()->create([
        'legacy_uid' => User::LEGACY_ADMIN_UID,
        'email' => 'ops@example.com',
    ]);

    $player = User::factory()->create([
        'username' => 'targetplayer',
        'email' => 'target@example.com',
    ]);

    actingAs($admin);
    actingAs($admin, 'admin');

    $this->withSession([]);

    Livewire::test(Dashboard::class)
        ->call('startImpersonation', $player->id)
        ->assertRedirect(route('home'));

    expect(session()->get('impersonation.active'))->toBeTrue()
        ->and(session()->get('impersonation.impersonated_name'))->toBe('targetplayer')
        ->and(Impersonation::query()->count())->toBe(1)
        ->and(Auth::guard('web')->id())->toBe($player->id)
        ->and(Auth::guard('admin')->id())->toBe($admin->id);
});

it('stops impersonation sessions via the controller endpoint', function (): void {
    $admin = User::factory()->create([
        'legacy_uid' => User::LEGACY_ADMIN_UID,
    ]);

    $player = User::factory()->create();

    actingAs($admin);
    actingAs($admin, 'admin');

    $this->withSession([]);

    app(ImpersonationManager::class)->start($admin, $player);

    delete(route('impersonation.destroy'))
        ->assertRedirect(route('admin.dashboard'));

    expect(session()->has('impersonation.active'))->toBeFalse()
        ->and(Auth::guard('web')->id())->toBe($admin->id)
        ->and(Auth::guard('admin')->id())->toBe($admin->id)
        ->and(Impersonation::query()->first()?->ended_at)->not->toBeNull();
});
