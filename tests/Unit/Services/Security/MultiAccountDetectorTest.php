<?php

declare(strict_types=1);

use App\Models\LoginActivity;
use App\Models\MultiAccountAlert;
use App\Models\User;
use App\Services\Security\MultiAccountDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('creates bidirectional alerts when accounts share an ip address', function (): void {
    $primary = User::factory()->create();
    $conflict = User::factory()->create();

    $ipAddress = '203.0.113.42';
    $timestamp = Carbon::parse('2025-04-15 09:30:00');

    LoginActivity::query()->create([
        'user_id' => $conflict->getKey(),
        'ip_address' => $ipAddress,
        'logged_at' => $timestamp->copy()->subMinutes(10),
    ]);

    $detector = app(MultiAccountDetector::class);
    $detector->record($primary, $ipAddress, $timestamp);

    $forward = MultiAccountAlert::query()
        ->where('primary_user_id', $primary->getKey())
        ->where('conflict_user_id', $conflict->getKey())
        ->first();

    $reverse = MultiAccountAlert::query()
        ->where('primary_user_id', $conflict->getKey())
        ->where('conflict_user_id', $primary->getKey())
        ->first();

    expect($forward)->not->toBeNull()
        ->and($reverse)->not->toBeNull()
        ->and($forward?->ip_address)->toBe($ipAddress)
        ->and($forward?->occurrences)->toBe(1)
        ->and($forward?->last_seen_at?->equalTo($timestamp))->toBeTrue()
        ->and($reverse?->occurrences)->toBe(1);
});

it('increments alert occurrences and updates timestamps for repeat conflicts', function (): void {
    $primary = User::factory()->create();
    $conflict = User::factory()->create();

    $ipAddress = '198.51.100.77';
    $firstSeen = Carbon::parse('2025-04-16 08:00:00');
    $secondSeen = Carbon::parse('2025-04-16 08:05:00');

    LoginActivity::query()->create([
        'user_id' => $conflict->getKey(),
        'ip_address' => $ipAddress,
        'logged_at' => $firstSeen->copy()->subMinutes(5),
    ]);

    $detector = app(MultiAccountDetector::class);
    $detector->record($primary, $ipAddress, $firstSeen);
    $detector->record($primary, $ipAddress, $secondSeen);

    $alert = MultiAccountAlert::query()
        ->where('primary_user_id', $primary->getKey())
        ->where('conflict_user_id', $conflict->getKey())
        ->first();

    expect($alert)->not->toBeNull()
        ->and($alert?->occurrences)->toBe(2)
        ->and($alert?->last_seen_at?->equalTo($secondSeen))->toBeTrue();
});

it('ignores empty ip addresses when recording activity', function (): void {
    $primary = User::factory()->create();

    $detector = app(MultiAccountDetector::class);
    $detector->record($primary, '', Carbon::now());

    expect(MultiAccountAlert::query()->count())->toBe(0);
});
