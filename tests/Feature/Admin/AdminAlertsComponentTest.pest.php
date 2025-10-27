<?php

declare(strict_types=1);

use App\Enums\MultiAccountAlertSeverity;
use App\Enums\MultiAccountAlertStatus;
use App\Livewire\Admin\Alerts;
use App\Models\MultiAccountAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('hashing.driver', 'bcrypt');

    if (! app()->bound('session')) {
        config()->set('session.driver', 'array');
        app()->register(\Illuminate\Session\SessionServiceProvider::class);
    }

    if (! session()->isStarted()) {
        session()->start();
    }

    Route::middleware('web')
        ->name('admin.multi-account-alerts.show')
        ->get('/testing/admin/multi-account-alerts/{alert}', static fn () => '');
});

/**
 * Ensure the admin alerts component lists alerts and supports severity filtering.
 */
it('lists alerts and filters by severity', function (): void {
    $admin = User::factory()->create([
        'legacy_uid' => User::LEGACY_ADMIN_UID,
    ]);

    $highAlert = MultiAccountAlert::factory()->create([
        'ip_address' => '203.0.113.17',
        'severity' => MultiAccountAlertSeverity::High,
        'status' => MultiAccountAlertStatus::Open,
        'user_ids' => [$admin->id, 42],
        'last_seen_at' => Carbon::now()->subMinutes(5),
        'source_type' => 'ip',
    ]);

    $lowAlert = MultiAccountAlert::factory()->create([
        'ip_address' => '198.51.100.44',
        'severity' => MultiAccountAlertSeverity::Low,
        'status' => MultiAccountAlertStatus::Open,
        'user_ids' => [$admin->id, 99],
        'last_seen_at' => Carbon::now()->subMinutes(10),
        'source_type' => 'ip',
    ]);

    actingAs($admin);
    actingAs($admin, 'admin');

    $component = Livewire::test(Alerts::class);

    $component
        ->assertSee('Multi-Account Alerts')
        ->assertSee($highAlert->ip_address)
        ->assertSee($lowAlert->ip_address);

    $component
        ->set('severity', MultiAccountAlertSeverity::High->value)
        ->assertSee($highAlert->ip_address)
        ->assertDontSee($lowAlert->ip_address);
});

/**
 * Confirm that administrators can resolve an alert while recording an audit note.
 */
it('resolves an alert with audit notes', function (): void {
    $admin = User::factory()->create([
        'legacy_uid' => User::LEGACY_ADMIN_UID,
    ]);

    $alert = MultiAccountAlert::factory()->create([
        'status' => MultiAccountAlertStatus::Open,
        'severity' => MultiAccountAlertSeverity::Critical,
        'user_ids' => [$admin->id, 777],
        'last_seen_at' => Carbon::now()->subMinutes(3),
        'ip_address' => '203.0.113.200',
        'source_type' => 'device',
    ]);

    actingAs($admin);
    actingAs($admin, 'admin');

    $component = Livewire::test(Alerts::class)
        ->call('confirmResolve', $alert->getKey());

    expect($component->get('actionContext'))
        ->not->toBeNull()
        ->and($component->get('actionType'))
        ->toBe('resolve');

    $component
        ->set('notes', '  Manual investigation cleared the accounts.  ')
        ->call('performAction')
        ->assertDispatched('admin.alerts:refresh');

    $alert->refresh();

    expect($alert->status)->toBe(MultiAccountAlertStatus::Resolved)
        ->and($alert->resolved_by_user_id)->toBe($admin->id)
        ->and($alert->notes)->toBe('Manual investigation cleared the accounts.');
});

/**
 * Confirm that administrators can dismiss alerts and store the supplied rationale.
 */
it('dismisses an alert with audit notes', function (): void {
    $admin = User::factory()->create([
        'legacy_uid' => User::LEGACY_ADMIN_UID,
    ]);

    $alert = MultiAccountAlert::factory()->create([
        'status' => MultiAccountAlertStatus::Open,
        'severity' => MultiAccountAlertSeverity::Medium,
        'user_ids' => [$admin->id, 555],
        'last_seen_at' => Carbon::now()->subMinutes(8),
        'ip_address' => '198.51.100.77',
        'source_type' => 'device',
    ]);

    actingAs($admin);
    actingAs($admin, 'admin');

    $component = Livewire::test(Alerts::class)
        ->call('confirmDismiss', $alert->getKey());

    expect($component->get('actionContext'))
        ->not->toBeNull()
        ->and($component->get('actionType'))
        ->toBe('dismiss');

    $component
        ->set('notes', 'False positive: shared gaming lounge network.')
        ->call('performAction')
        ->assertDispatched('admin.alerts:refresh');

    $alert->refresh();

    expect($alert->status)->toBe(MultiAccountAlertStatus::Dismissed)
        ->and($alert->dismissed_by_user_id)->toBe($admin->id)
        ->and($alert->notes)->toBe('False positive: shared gaming lounge network.');
});
