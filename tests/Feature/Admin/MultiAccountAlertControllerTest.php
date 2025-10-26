<?php

declare(strict_types=1);

use App\Enums\MultiAccountAlertSeverity;
use App\Enums\MultiAccountAlertStatus;
use App\Models\MultiAccountAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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

it('renders the multi-account alerts index for administrators', function (): void {
    $admin = User::factory()->create(['legacy_uid' => User::LEGACY_ADMIN_UID]);

    $alert = MultiAccountAlert::factory()->create([
        'ip_address' => '203.0.113.10',
        'device_hash' => 'device-index',
        'user_ids' => [1, 2],
        'occurrences' => 3,
    ]);

    actingAs($admin);
    actingAs($admin, 'admin');

    $response = $this->get(route('admin.multi-account-alerts.index'));

    $response->assertOk();
    $response->assertSee('Multi-Account Alerts');
    $response->assertSee($alert->ip_address);
});

it('prevents regular players from accessing the alerts index', function (): void {
    $player = User::factory()->create();

    actingAs($player);

    $this->get(route('admin.multi-account-alerts.index'))
        ->assertRedirect(route('login'));
});

it('resolves and dismisses alerts via the controller actions', function (): void {
    $admin = User::factory()->create(['legacy_uid' => User::LEGACY_ADMIN_UID]);

    actingAs($admin);
    actingAs($admin, 'admin');

    $alert = MultiAccountAlert::factory()->create([
        'status' => MultiAccountAlertStatus::Open,
        'severity' => MultiAccountAlertSeverity::High,
        'user_ids' => [$admin->id],
        'occurrences' => 4,
        'ip_address' => '198.51.100.8',
        'device_hash' => 'device-resolve',
    ]);

    $resolveResponse = $this->post(route('admin.multi-account-alerts.resolve', $alert), [
        'notes' => 'Reviewed and resolved by automation.',
    ]);

    $resolveResponse->assertRedirect(route('admin.multi-account-alerts.show', $alert));

    $alert->refresh();
    expect($alert->status)->toBe(MultiAccountAlertStatus::Resolved)
        ->and($alert->resolved_by_user_id)->toBe($admin->id)
        ->and($alert->notes)->toBe('Reviewed and resolved by automation.');

    $dismissResponse = $this->post(route('admin.multi-account-alerts.dismiss', $alert), [
        'notes' => 'Escalation invalidated after review.',
    ]);

    $dismissResponse->assertRedirect(route('admin.multi-account-alerts.show', $alert));

    $alert->refresh();
    expect($alert->status)->toBe(MultiAccountAlertStatus::Dismissed)
        ->and($alert->dismissed_by_user_id)->toBe($admin->id)
        ->and(Str::contains($alert->notes ?? '', 'Escalation invalidated'))->toBeTrue();
});

it('returns ip intelligence data via the lookup endpoint', function (): void {
    $admin = User::factory()->create(['legacy_uid' => User::LEGACY_ADMIN_UID]);

    actingAs($admin);
    actingAs($admin, 'admin');

    Http::fake([
        'http://ip-api.com/*' => Http::response([
            'status' => 'success',
            'query' => '203.0.113.99',
            'country' => 'Example',
            'regionName' => 'Test Region',
            'city' => 'Test City',
            'isp' => 'Example ISP',
            'as' => 'AS12345 Example',
            'timezone' => 'UTC',
            'proxy' => true,
        ]),
    ]);

    config()->set('multiaccount.notifications.webhook_url', null);
    config()->set('multiaccount.ip_lookup.provider', 'ip-api');

    $response = $this->getJson(route('admin.multi-account-alerts.ip-lookup', ['ip' => '203.0.113.99']));

    $response->assertOk();
    $response->assertJsonPath('data.ip', '203.0.113.99');
    $response->assertJsonPath('data.isp', 'Example ISP');
});
