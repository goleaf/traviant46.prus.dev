<?php

declare(strict_types=1);

use App\Enums\MultiAccountAlertSeverity;
use App\Enums\MultiAccountAlertStatus;
use App\Livewire\Admin\Alerts as AlertsComponent;
use App\Models\MultiAccountAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    if (! app()->bound('session')) {
        config()->set('session.driver', 'array');
        app()->register(Illuminate\Session\SessionServiceProvider::class);
    }

    if (! session()->isStarted()) {
        session()->start();
    }
});

it('filters alerts by severity within the Livewire component', function (): void {
    $admin = User::factory()->create(['legacy_uid' => User::LEGACY_ADMIN_UID]);

    $critical = MultiAccountAlert::factory()->create([
        'severity' => MultiAccountAlertSeverity::Critical,
        'status' => MultiAccountAlertStatus::Open,
        'source_type' => 'ip',
        'ip_address' => '203.0.113.9',
        'user_ids' => [1, 2],
        'occurrences' => 4,
    ]);

    $low = MultiAccountAlert::factory()->create([
        'severity' => MultiAccountAlertSeverity::Low,
        'status' => MultiAccountAlertStatus::Open,
        'source_type' => 'device',
        'device_hash' => 'device-low-123',
        'user_ids' => [3, 4],
        'occurrences' => 2,
    ]);

    actingAs($admin);
    actingAs($admin, 'admin');

    Livewire::test(AlertsComponent::class)
        ->assertSee($critical->ip_address)
        ->assertSee($low->device_hash)
        ->set('severity', MultiAccountAlertSeverity::Critical->value)
        ->assertSee($critical->ip_address)
        ->assertDontSee($low->device_hash);
});

it('resolves and dismisses alerts with audit notes via Livewire actions', function (): void {
    $admin = User::factory()->create(['legacy_uid' => User::LEGACY_ADMIN_UID]);

    $resolveTarget = MultiAccountAlert::factory()->create([
        'severity' => MultiAccountAlertSeverity::High,
        'status' => MultiAccountAlertStatus::Open,
        'source_type' => 'ip',
        'ip_address' => '198.51.100.14',
        'user_ids' => [$admin->id, 42],
        'occurrences' => 5,
    ]);

    $dismissTarget = MultiAccountAlert::factory()->create([
        'severity' => MultiAccountAlertSeverity::Medium,
        'status' => MultiAccountAlertStatus::Open,
        'source_type' => 'device',
        'device_hash' => 'device-dismiss-456',
        'user_ids' => [$admin->id, 101],
        'occurrences' => 3,
    ]);

    actingAs($admin);
    actingAs($admin, 'admin');

    Livewire::test(AlertsComponent::class)
        ->call('confirmResolve', $resolveTarget->getKey())
        ->set('notes', 'Manual correlation confirmed matching sitter rotation.')
        ->call('performAction')
        ->assertSet('actionAlertId', null)
        ->assertSet('actionContext', null)
        ->call('confirmDismiss', $dismissTarget->getKey())
        ->set('notes', 'Dismissed after VPN allowlist verified.')
        ->call('performAction')
        ->assertSet('actionAlertId', null)
        ->assertSet('actionContext', null);

    $resolveTarget->refresh();
    expect($resolveTarget->status)->toBe(MultiAccountAlertStatus::Resolved)
        ->and($resolveTarget->notes)->toBe('Manual correlation confirmed matching sitter rotation.')
        ->and($resolveTarget->resolved_by_user_id)->toBe($admin->id);

    $dismissTarget->refresh();
    expect($dismissTarget->status)->toBe(MultiAccountAlertStatus::Dismissed)
        ->and($dismissTarget->notes)->toBe('Dismissed after VPN allowlist verified.')
        ->and($dismissTarget->dismissed_by_user_id)->toBe($admin->id);
});
