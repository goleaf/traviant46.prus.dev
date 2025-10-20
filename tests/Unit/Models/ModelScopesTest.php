<?php

use App\Models\Activation;
use App\Models\LoginActivity;
use App\Models\LoginIpLog;
use App\Models\MultiAccountAlert;
use App\Models\SitterAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('filters activations by world and usage state', function (): void {
    $first = Activation::query()->create([
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'token' => Str::random(32),
        'password' => bcrypt('secret'),
        'world_id' => 's1',
        'used' => false,
    ]);

    $second = Activation::query()->create([
        'name' => 'Bob',
        'email' => 'bob@example.com',
        'token' => Str::random(32),
        'password' => bcrypt('secret'),
        'world_id' => 's2',
        'used' => true,
    ]);

    $third = Activation::query()->create([
        'name' => 'Cara',
        'email' => 'cara@example.com',
        'token' => Str::random(32),
        'password' => bcrypt('secret'),
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

    $first = LoginActivity::query()->create([
        'user_id' => $primary->id,
        'ip_address' => '10.0.0.1',
        'user_agent' => 'Test',
        'via_sitter' => false,
        'created_at' => $now->copy()->subMinutes(30),
        'updated_at' => $now->copy()->subMinutes(30),
    ]);

    $second = LoginActivity::query()->create([
        'user_id' => $primary->id,
        'acting_sitter_id' => $secondary->id,
        'ip_address' => '10.0.0.2',
        'user_agent' => 'Browser',
        'via_sitter' => true,
        'created_at' => $now->copy()->subMinutes(10),
        'updated_at' => $now->copy()->subMinutes(10),
    ]);

    $third = LoginActivity::query()->create([
        'user_id' => $secondary->id,
        'ip_address' => '10.0.0.1',
        'user_agent' => 'Other',
        'via_sitter' => false,
        'created_at' => $now->copy()->subDay(),
        'updated_at' => $now->copy()->subDay(),
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
        'ip_address' => '203.0.113.5',
        'primary_user_id' => $primary->id,
        'conflict_user_id' => $conflict->id,
        'occurrences' => 2,
        'last_seen_at' => $now->copy()->subMinutes(5),
    ]);

    MultiAccountAlert::query()->create([
        'ip_address' => '203.0.113.5',
        'primary_user_id' => $primary->id,
        'conflict_user_id' => $other->id,
        'occurrences' => 1,
        'last_seen_at' => $now->copy()->subHours(3),
    ]);

    MultiAccountAlert::query()->create([
        'ip_address' => '198.51.100.99',
        'primary_user_id' => $other->id,
        'conflict_user_id' => $conflict->id,
        'occurrences' => 4,
        'last_seen_at' => $now->copy()->subDay(),
    ]);

    expect(MultiAccountAlert::forIp('203.0.113.5')->count())->toBe(2);
    expect(MultiAccountAlert::involvingUser($conflict)->pluck('id'))->toContain($first->id);
    expect(MultiAccountAlert::recent($now->copy()->subHour())->sole()->is($first))->toBeTrue();
});

it('filters sitter assignments for account, sitter and activity', function (): void {
    [$account, $sitter, $other] = User::factory()->count(3)->create();
    $now = Carbon::parse('2025-03-25 14:00:00');

    $active = SitterAssignment::query()->create([
        'account_id' => $account->id,
        'sitter_id' => $sitter->id,
        'permissions' => ['send_troops'],
        'expires_at' => $now->copy()->addDay(),
    ]);

    $expired = SitterAssignment::query()->create([
        'account_id' => $account->id,
        'sitter_id' => $other->id,
        'permissions' => ['read_messages'],
        'expires_at' => $now->copy()->subHours(2),
    ]);

    $permanent = SitterAssignment::query()->create([
        'account_id' => $other->id,
        'sitter_id' => $sitter->id,
        'permissions' => ['trade'],
        'expires_at' => null,
    ]);

    expect(SitterAssignment::forAccount($account)->count())->toBe(2);
    expect(SitterAssignment::forSitter($sitter)->pluck('id'))->toContain($active->id);
    expect(SitterAssignment::active($now)->pluck('id'))
        ->toContain($active->id, $permanent->id)
        ->not->toContain($expired->id);
});
