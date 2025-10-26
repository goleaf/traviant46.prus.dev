<?php

declare(strict_types=1);

use App\Jobs\RebuildMultiAccountAlerts;
use App\Models\LoginActivity;
use App\Models\MultiAccountAlert;
use App\Models\User;
use App\Services\Security\MultiAccountDetector;
use App\Services\Security\MultiAccountRules;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite', [
        'driver' => 'sqlite',
        'database' => database_path('testing.sqlite'),
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);
    config()->set('cache.default', 'array');

    $databasePath = database_path('testing.sqlite');
    if (! file_exists($databasePath)) {
        touch($databasePath);
    }

    DB::purge();
    DB::reconnect();
    DB::connection()->getPdo()->exec('PRAGMA foreign_keys = ON;');

    Schema::dropIfExists('multi_account_alerts');
    Schema::dropIfExists('login_activities');
    Schema::dropIfExists('users');

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->unsignedInteger('legacy_uid')->unique();
        $table->string('username')->unique();
        $table->string('name')->nullable();
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->string('role')->nullable();
        $table->rememberToken();
        $table->boolean('is_banned')->default(false);
        $table->string('ban_reason')->nullable();
        $table->timestamp('ban_issued_at')->nullable();
        $table->timestamp('ban_expires_at')->nullable();
        $table->timestamps();
    });

    Schema::create('login_activities', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
        $table->foreignId('acting_sitter_id')->nullable()->constrained('users')->nullOnDelete();
        $table->string('ip_address')->nullable();
        $table->string('user_agent')->nullable();
        $table->string('device_hash')->nullable();
        $table->timestamp('logged_at')->nullable();
        $table->boolean('via_sitter')->default(false);
        $table->timestamps();
    });

    Schema::create('multi_account_alerts', function (Blueprint $table): void {
        $table->id();
        $table->uuid('alert_id')->unique();
        $table->string('group_key')->unique();
        $table->string('source_type');
        $table->string('ip_address')->nullable();
        $table->string('device_hash')->nullable();
        $table->json('user_ids');
        $table->unsignedInteger('occurrences')->default(0);
        $table->timestamp('first_seen_at')->nullable();
        $table->timestamp('window_started_at')->nullable();
        $table->timestamp('last_seen_at')->nullable();
        $table->string('severity')->nullable();
        $table->string('status')->nullable();
        $table->string('suppression_reason')->nullable();
        $table->timestamp('resolved_at')->nullable();
        $table->unsignedBigInteger('resolved_by_user_id')->nullable();
        $table->timestamp('dismissed_at')->nullable();
        $table->unsignedBigInteger('dismissed_by_user_id')->nullable();
        $table->text('notes')->nullable();
        $table->json('metadata')->nullable();
        $table->timestamp('last_notified_at')->nullable();
        $table->timestamps();
    });

    config()->set('multiaccount.thresholds', []);
    config()->set('multiaccount.allowlist', [
        'ip_addresses' => [],
        'cidr_ranges' => [],
        'device_hashes' => [],
    ]);
    config()->set('multiaccount.vpn_indicators', [
        'user_agent_keywords' => [],
        'cidr_ranges' => [],
    ]);

    app()->forgetInstance(MultiAccountRules::class);
});

it('rebuilds multi account alerts without triggering notifications', function (): void {
    Notification::fake();

    $primary = User::factory()->create();
    $conflict = User::factory()->create();

    $timestamp = Carbon::parse('2025-05-01 07:30:00');

    LoginActivity::query()->insert([
        [
            'user_id' => $conflict->getKey(),
            'ip_address' => '198.51.100.10',
            'user_agent' => 'BrowserA',
            'device_hash' => 'device-conflict',
            'logged_at' => $timestamp->copy()->subMinutes(10),
            'created_at' => $timestamp->copy()->subMinutes(10),
            'updated_at' => $timestamp->copy()->subMinutes(10),
        ],
        [
            'user_id' => $primary->getKey(),
            'ip_address' => '198.51.100.10',
            'user_agent' => 'BrowserB',
            'device_hash' => 'device-primary',
            'logged_at' => $timestamp,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
    ]);

    $job = new RebuildMultiAccountAlerts;
    $job->handle(app(MultiAccountDetector::class));

    $alert = MultiAccountAlert::query()->first();

    expect($alert)->not->toBeNull()
        ->and($alert?->user_ids)->toEqualCanonicalizing([$conflict->getKey(), $primary->getKey()])
        ->and($alert?->occurrences)->toBe(2);

    Notification::assertNothingSent();
});
