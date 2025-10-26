<?php

declare(strict_types=1);

use App\Enums\MultiAccountAlertSeverity;
use App\Enums\MultiAccountAlertStatus;
use App\Models\MultiAccountAlert;
use App\Models\User;
use App\Services\Security\MultiAccountAlertsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('creates and updates multi-account alerts via the service layer', function (): void {
    Notification::fake();

    /** @var MultiAccountAlertsService $service */
    $service = app(MultiAccountAlertsService::class);

    $groupKey = 'test-group-key';
    $firstSeen = Carbon::now()->subMinutes(20);
    $windowStarted = Carbon::now()->subMinutes(15);
    $lastSeen = Carbon::now();

    $service->upsert(
        groupKey: $groupKey,
        sourceType: 'ip',
        worldId: 's1',
        ipAddress: '203.0.113.10',
        ipAddressHash: 'hash-1',
        deviceHash: null,
        userIds: [1, 2],
        occurrences: 3,
        windowStartedAt: $windowStarted,
        firstSeenAt: $firstSeen,
        lastSeenAt: $lastSeen,
        severity: MultiAccountAlertSeverity::High,
        status: MultiAccountAlertStatus::Open,
        suppressionReason: null,
        metadata: [
            'source' => [
                'type' => 'ip',
                'identifier' => '203.0.113.10',
                'identifiers' => ['203.0.113.10'],
            ],
        ],
        shouldNotify: false,
    );

    $alert = MultiAccountAlert::query()->firstOrFail();

    expect($alert->group_key)->toBe($groupKey)
        ->and($alert->world_id)->toBe('s1')
        ->and($alert->severity)->toBe(MultiAccountAlertSeverity::High)
        ->and($alert->status)->toBe(MultiAccountAlertStatus::Open)
        ->and($alert->occurrences)->toBe(3);

    $service->upsert(
        groupKey: $groupKey,
        sourceType: 'ip',
        worldId: 's1',
        ipAddress: '203.0.113.10',
        ipAddressHash: 'hash-1',
        deviceHash: null,
        userIds: [1, 2],
        occurrences: 5,
        windowStartedAt: $windowStarted,
        firstSeenAt: $firstSeen,
        lastSeenAt: $lastSeen->copy()->addMinutes(5),
        severity: MultiAccountAlertSeverity::Critical,
        status: MultiAccountAlertStatus::Open,
        suppressionReason: null,
        metadata: [
            'source' => [
                'type' => 'ip',
                'identifier' => '203.0.113.10',
                'identifiers' => ['203.0.113.10'],
            ],
        ],
        shouldNotify: false,
    );

    $alert->refresh();

    expect($alert->occurrences)->toBe(5)
        ->and($alert->severity)->toBe(MultiAccountAlertSeverity::Critical)
        ->and($alert->last_seen_at?->greaterThan($lastSeen))->toBeTrue();
});

it('resolves and dismisses alerts through the service', function (): void {
    Notification::fake();

    /** @var MultiAccountAlertsService $service */
    $service = app(MultiAccountAlertsService::class);

    $alert = MultiAccountAlert::factory()->create([
        'status' => MultiAccountAlertStatus::Open,
        'severity' => MultiAccountAlertSeverity::High,
        'user_ids' => [1, 2],
        'world_id' => 's2',
    ]);

    $actor = User::factory()->create();

    $service->resolve($alert, $actor, 'Investigated and resolved.');

    $alert->refresh();

    expect($alert->status)->toBe(MultiAccountAlertStatus::Resolved)
        ->and($alert->resolved_by_user_id)->toBe($actor->id)
        ->and($alert->notes)->toBe('Investigated and resolved.');

    $service->dismiss($alert, $actor, 'No longer relevant.');

    $alert->refresh();

    expect($alert->status)->toBe(MultiAccountAlertStatus::Dismissed)
        ->and($alert->dismissed_by_user_id)->toBe($actor->id)
        ->and($alert->notes)->toBe('No longer relevant.');
});
