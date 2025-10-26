<?php

declare(strict_types=1);

use App\Enums\MultiAccountAlertSeverity;
use App\Enums\MultiAccountAlertStatus;
use App\Models\LoginActivity;
use App\Models\MultiAccountAlert;
use App\Models\User;
use App\Monitoring\Metrics\MetricRecorder;
use App\Notifications\MultiAccountAlertRaised;
use App\Services\Security\MultiAccountDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('multiaccount.thresholds', [
        'low' => ['unique_accounts' => 2, 'occurrences' => 2],
        'medium' => ['unique_accounts' => 2, 'occurrences' => 3],
        'high' => ['unique_accounts' => 3, 'occurrences' => 4],
        'critical' => ['unique_accounts' => 4, 'occurrences' => 6],
    ]);

    config()->set('multiaccount.allowlist', [
        'ip_addresses' => [],
        'cidr_ranges' => [],
        'device_hashes' => [],
    ]);

    config()->set('multiaccount.notifications.webhook_url', null);

    app()->bind(MetricRecorder::class, fn () => new class implements MetricRecorder
    {
        public function increment(string $metric, float $value = 1.0, array $tags = []): void
        {
            // no-op for tests
        }
    });
});

it('records an ip-based alert when thresholds are met', function (): void {
    $ipAddress = '203.0.113.42';
    $now = Carbon::parse('2025-05-01 12:00:00');
    Carbon::setTestNow($now);

    [$primary, $conflict] = User::factory()->count(2)->create();

    LoginActivity::factory()->for($conflict, 'user')->create([
        'ip_address' => $ipAddress,
        'ip_address_hash' => hash('sha256', $ipAddress),
        'device_hash' => 'device-conflict',
        'logged_at' => $now->copy()->subMinutes(10),
    ]);

    $activity = LoginActivity::factory()->for($primary, 'user')->create([
        'ip_address' => $ipAddress,
        'ip_address_hash' => hash('sha256', $ipAddress),
        'device_hash' => 'device-primary',
        'logged_at' => $now,
        'user_agent' => 'Mozilla/5.0',
    ]);

    Notification::fake();

    $detector = app(MultiAccountDetector::class);
    $detector->record($activity);

    $alert = MultiAccountAlert::query()->firstOrFail();

    expect($alert->source_type)->toBe('ip')
        ->and($alert->severity)->toBe(MultiAccountAlertSeverity::Low)
        ->and($alert->status)->toBe(MultiAccountAlertStatus::Open)
        ->and($alert->user_ids)->toEqualCanonicalizing([$primary->id, $conflict->id])
        ->and($alert->occurrences)->toBe(2)
        ->and($alert->metadata['user_counts'][(string) $primary->id] ?? $alert->metadata['user_counts'][$primary->id] ?? null)->toBe(1)
        ->and($alert->metadata['user_counts'][(string) $conflict->id] ?? $alert->metadata['user_counts'][$conflict->id] ?? null)->toBe(1);

    Notification::assertNothingSent();

    Carbon::setTestNow();
});

it('groups alerts by device hash when configured', function (): void {
    $deviceHash = 'device-shared';
    $now = Carbon::parse('2025-05-02 08:45:00');
    Carbon::setTestNow($now);

    [$primary, $conflict] = User::factory()->count(2)->create();

    LoginActivity::factory()->for($conflict, 'user')->create([
        'ip_address' => '198.51.100.10',
        'device_hash' => $deviceHash,
        'logged_at' => $now->copy()->subMinutes(20),
    ]);

    $activity = LoginActivity::factory()->for($primary, 'user')->create([
        'ip_address' => '198.51.100.11',
        'device_hash' => $deviceHash,
        'logged_at' => $now,
        'user_agent' => 'Safari/605',
    ]);

    app(MultiAccountDetector::class)->record($activity);

    $alert = MultiAccountAlert::query()->firstOrFail();

    expect($alert->source_type)->toBe('device')
        ->and($alert->device_hash)->toBe($deviceHash)
        ->and($alert->user_ids)->toEqualCanonicalizing([$primary->id, $conflict->id]);

    Carbon::setTestNow();
});

it('marks alerts as suppressed when the source is allowlisted', function (): void {
    $ipAddress = '203.0.113.50';
    config()->set('multiaccount.allowlist.ip_addresses', [$ipAddress]);

    [$primary, $conflict] = User::factory()->count(2)->create();

    $timestamp = Carbon::parse('2025-05-03 07:15:00');
    Carbon::setTestNow($timestamp);

    LoginActivity::factory()->for($conflict, 'user')->create([
        'ip_address' => $ipAddress,
        'device_hash' => 'suppressed-device',
        'logged_at' => $timestamp->copy()->subMinutes(15),
    ]);

    $activity = LoginActivity::factory()->for($primary, 'user')->create([
        'ip_address' => $ipAddress,
        'device_hash' => 'suppressed-device',
        'logged_at' => $timestamp,
        'user_agent' => 'Chrome',
    ]);

    app(MultiAccountDetector::class)->record($activity);

    $alert = MultiAccountAlert::query()->firstOrFail();

    expect($alert->status)->toBe(MultiAccountAlertStatus::Suppressed)
        ->and($alert->suppression_reason)->toBe('allowlisted_ip')
        ->and($alert->severity)->toBe(MultiAccountAlertSeverity::Low);

    Carbon::setTestNow();
});

it('sends notifications and webhook payloads for high severity alerts', function (): void {
    $ipAddress = '198.51.100.77';
    $now = Carbon::parse('2025-05-04 10:00:00');
    Carbon::setTestNow($now);

    $primary = User::factory()->create();
    $firstConflict = User::factory()->create();
    $secondConflict = User::factory()->create();

    $admin = User::factory()->create(['legacy_uid' => 0]);
    $multihunter = User::factory()->create(['legacy_uid' => 2]);

    config()->set('multiaccount.notifications.webhook_url', 'https://example.org/webhook');

    Notification::fake();
    Http::fake([
        'https://example.org/*' => Http::response(['ok' => true], 200),
    ]);

    LoginActivity::factory()->for($firstConflict, 'user')->create([
        'ip_address' => $ipAddress,
        'device_hash' => 'device-shared',
        'logged_at' => $now->copy()->subMinutes(15),
    ]);

    LoginActivity::factory()->for($firstConflict, 'user')->create([
        'ip_address' => $ipAddress,
        'device_hash' => 'device-shared',
        'logged_at' => $now->copy()->subMinutes(10),
    ]);

    LoginActivity::factory()->for($secondConflict, 'user')->create([
        'ip_address' => $ipAddress,
        'device_hash' => 'device-shared',
        'logged_at' => $now->copy()->subMinutes(5),
    ]);

    $activity = LoginActivity::factory()->for($primary, 'user')->create([
        'ip_address' => $ipAddress,
        'device_hash' => 'device-shared',
        'logged_at' => $now,
        'user_agent' => 'Firefox',
    ]);

    app(MultiAccountDetector::class)->record($activity);

    $alert = MultiAccountAlert::query()->firstOrFail();

    expect($alert->severity)->toBe(MultiAccountAlertSeverity::High)
        ->and($alert->status)->toBe(MultiAccountAlertStatus::Open)
        ->and($alert->last_notified_at)->not->toBeNull();

    Notification::assertSentTo($admin, MultiAccountAlertRaised::class);
    Notification::assertSentTo($multihunter, MultiAccountAlertRaised::class);

    Http::assertSent(function ($request) use ($alert) {
        return $request->url() === 'https://example.org/webhook'
            && ($request['alert_id'] ?? null) === $alert->alert_id
            && ($request['severity'] ?? null) === $alert->severity?->value;
    });

    Carbon::setTestNow();
});
