<?php

declare(strict_types=1);

use App\Enums\MultiAccountAlertSeverity;
use App\Enums\MultiAccountAlertStatus;
use App\Enums\SitterPermission;
use App\Models\Activation;
use App\Models\LoginActivity;
use App\Models\LoginIpLog;
use App\Models\MultiAccountAlert;
use App\Models\SitterDelegation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('filters activations by world and usage state', function (): void {
    $first = Activation::query()->create([
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'token' => Str::random(32),
        'password' => Hash::make('Secret#1234AB'),
        'world_id' => 's1',
        'used' => false,
    ]);

    $second = Activation::query()->create([
        'name' => 'Bob',
        'email' => 'bob@example.com',
        'token' => Str::random(32),
        'password' => Hash::make('Secret#1234AB'),
        'world_id' => 's2',
        'used' => true,
    ]);

    $third = Activation::query()->create([
        'name' => 'Cara',
        'email' => 'cara@example.com',
        'token' => Str::random(32),
        'password' => Hash::make('Secret#1234AB'),
        'world_id' => null,
        'used' => false,
    ]);

    expect(Activation::forWorld('s1')->sole()->is($first))->toBeTrue();
    expect(Activation::forWorld(null)->sole()->is($third))->toBeTrue();
    expect(Activation::used()->sole()->is($second))->toBeTrue();
    expect(Activation::unused()->pluck('id'))->toContain($first->id, $third->id)->not->toContain($second->id);
    expect(Activation::token($second->token)->sole()->is($second))->toBeTrue();
});

it('filters login activities using the defined scopes', function (): void {
    [$primary, $secondary] = User::factory()->count(2)->create();
    $now = Carbon::parse('2025-03-15 12:00:00');
    Carbon::setTestNow($now);

    $firstLoggedAt = $now->copy()->subMinutes(30);
    $secondLoggedAt = $now->copy()->subMinutes(10);
    $thirdLoggedAt = $now->copy()->subDay();

    $first = LoginActivity::query()->create([
        'user_id' => $primary->id,
        'ip_address' => '10.0.0.1',
        'ip_address_hash' => hash('sha256', '10.0.0.1'),
        'user_agent' => 'Test',
        'device_hash' => 'device-first',
        'via_sitter' => false,
        'logged_at' => $firstLoggedAt,
        'created_at' => $firstLoggedAt,
        'updated_at' => $firstLoggedAt,
    ]);

    $second = LoginActivity::query()->create([
        'user_id' => $primary->id,
        'acting_sitter_id' => $secondary->id,
        'ip_address' => '10.0.0.2',
        'ip_address_hash' => hash('sha256', '10.0.0.2'),
        'user_agent' => 'Browser',
        'device_hash' => 'device-second',
        'via_sitter' => true,
        'logged_at' => $secondLoggedAt,
        'created_at' => $secondLoggedAt,
        'updated_at' => $secondLoggedAt,
    ]);

    $third = LoginActivity::query()->create([
        'user_id' => $secondary->id,
        'ip_address' => '10.0.0.1',
        'ip_address_hash' => hash('sha256', '10.0.0.1'),
        'user_agent' => 'Other',
        'device_hash' => 'device-third',
        'via_sitter' => false,
        'logged_at' => $thirdLoggedAt,
        'created_at' => $thirdLoggedAt,
        'updated_at' => $thirdLoggedAt,
    ]);

    expect(LoginActivity::forUser($primary)->count())->toBe(2);
    expect(LoginActivity::fromIp('10.0.0.1')->pluck('id'))->toContain($first->id, $third->id)->not->toContain($second->id);
    expect(LoginActivity::viaSitter()->sole()->is($second))->toBeTrue();
    expect(LoginActivity::exceptUser($primary)->pluck('id'))->toContain($third->id)->not->toContain($first->id, $second->id);
    expect(LoginActivity::within($now->copy()->subHour(), $now)->pluck('id'))->toContain($first->id, $second->id)->not->toContain($third->id);

    Carbon::setTestNow();
});

it('filters login ip logs by user, ip and time window', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $now = Carbon::parse('2025-03-16 09:30:00');

    $first = LoginIpLog::query()->create([
        'user_id' => $user->id,
        'ip_address' => '192.168.0.1',
        'ip_address_numeric' => ip2long('192.168.0.1'),
        'recorded_at' => $now->copy()->subMinutes(20),
    ]);

    $second = LoginIpLog::query()->create([
        'user_id' => $user->id,
        'ip_address' => '192.168.0.2',
        'ip_address_numeric' => ip2long('192.168.0.2'),
        'recorded_at' => $now->copy()->subMinutes(5),
    ]);

    LoginIpLog::query()->create([
        'user_id' => $other->id,
        'ip_address' => '192.168.0.1',
        'ip_address_numeric' => ip2long('192.168.0.1'),
        'recorded_at' => $now->copy()->subHour(),
    ]);

    expect(LoginIpLog::forUser($user)->pluck('id'))->toContain($first->id, $second->id);
    expect(LoginIpLog::fromIp('192.168.0.1')->pluck('id'))->toContain($first->id);
    expect(LoginIpLog::recordedBetween($now->copy()->subMinutes(15), $now)->sole()->is($second))->toBeTrue();
});

it('filters multi account alerts with helper scopes', function (): void {
    [$primary, $conflict, $other] = User::factory()->count(3)->create();
    $now = Carbon::parse('2025-03-20 08:00:00');

    $first = MultiAccountAlert::query()->create([
        'alert_id' => Str::uuid()->toString(),
        'group_key' => sha1('203.0.113.5|'.$primary->id.'-'.$conflict->id),
        'source_type' => 'ip',
        'ip_address' => '203.0.113.5',
        'user_ids' => [$primary->id, $conflict->id],
        'occurrences' => 3,
        'first_seen_at' => $now->copy()->subMinutes(45),
        'last_seen_at' => $now->copy()->subMinutes(5),
        'severity' => MultiAccountAlertSeverity::Medium,
        'status' => MultiAccountAlertStatus::Open,
    ]);

    MultiAccountAlert::query()->create([
        'alert_id' => Str::uuid()->toString(),
        'group_key' => sha1('203.0.113.5|'.$primary->id.'-'.$other->id),
        'source_type' => 'ip',
        'ip_address' => '203.0.113.5',
        'user_ids' => [$primary->id, $other->id],
        'first_seen_at' => $now->copy()->subHours(4),
        'last_seen_at' => $now->copy()->subHours(3),
        'severity' => MultiAccountAlertSeverity::Low,
        'status' => MultiAccountAlertStatus::Open,
    ]);

    MultiAccountAlert::query()->create([
        'alert_id' => Str::uuid()->toString(),
        'group_key' => sha1('198.51.100.99|'.$other->id.'-'.$conflict->id),
        'source_type' => 'ip',
        'ip_address' => '198.51.100.99',
        'user_ids' => [$other->id, $conflict->id],
        'first_seen_at' => $now->copy()->subDays(2),
        'last_seen_at' => $now->copy()->subDay(),
        'severity' => MultiAccountAlertSeverity::High,
        'status' => MultiAccountAlertStatus::Open,
    ]);

    expect(MultiAccountAlert::forIp('203.0.113.5')->count())->toBe(2);
    expect(MultiAccountAlert::involvingUser($conflict)->pluck('id'))->toContain($first->id);
    expect(MultiAccountAlert::recent($now->copy()->subHour())->sole()->is($first))->toBeTrue();
});

it('filters sitter assignments for account, sitter and activity', function (): void {
    [$account, $sitter, $other] = User::factory()->count(3)->create();
    $now = Carbon::parse('2025-03-25 14:00:00');

    $active = SitterDelegation::query()->create([
        'owner_user_id' => $account->id,
        'sitter_user_id' => $sitter->id,
        'permissions' => [SitterPermission::SendTroops->key()],
        'expires_at' => $now->copy()->addDay(),
        'created_by' => $account->id,
        'updated_by' => $account->id,
    ]);

    $expired = SitterDelegation::query()->create([
        'owner_user_id' => $account->id,
        'sitter_user_id' => $other->id,
        'permissions' => [SitterPermission::Farm->key()],
        'expires_at' => $now->copy()->subHours(2),
        'created_by' => $account->id,
        'updated_by' => $account->id,
    ]);

    $permanent = SitterDelegation::query()->create([
        'owner_user_id' => $other->id,
        'sitter_user_id' => $sitter->id,
        'permissions' => [SitterPermission::Trade->key()],
        'expires_at' => null,
        'created_by' => $other->id,
        'updated_by' => $other->id,
    ]);

    expect(SitterDelegation::forAccount($account)->count())->toBe(2);
    expect(SitterDelegation::forSitter($sitter)->pluck('id'))->toContain($active->id);
    expect(SitterDelegation::active($now)->pluck('id'))
        ->toContain($active->id, $permanent->id)
        ->not->toContain($expired->id);
});
