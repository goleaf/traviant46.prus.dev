<?php

declare(strict_types=1);

use App\Livewire\Admin\PlayerAudit;
use App\Models\Game\MovementOrder;
use App\Models\Game\Village;
use App\Models\LoginActivity;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    if (! app()->bound('session')) {
        config()->set('session.driver', 'array');
        app()->register(\Illuminate\Session\SessionServiceProvider::class);
    }

    if (! session()->isStarted()) {
        session()->start();
    }
});

it('generates an audit transcript for a matching username', function (): void {
    $admin = User::factory()->create([
        'legacy_uid' => User::LEGACY_ADMIN_UID,
        'username' => 'command',
        'email' => 'admin@example.com',
    ]);

    actingAs($admin);
    actingAs($admin, 'admin');

    $player = User::factory()->create([
        'username' => 'audit-target',
        'legacy_uid' => 4321,
        'email' => 'target@example.com',
    ]);

    $homeVillage = Village::factory()->create([
        'user_id' => $player->getKey(),
        'name' => 'Home Village',
        'population' => 180,
        'x_coordinate' => -12,
        'y_coordinate' => 44,
    ]);

    $outpostVillage = Village::factory()->create([
        'user_id' => $player->getKey(),
        'name' => 'Border Outpost',
        'population' => 90,
        'x_coordinate' => 7,
        'y_coordinate' => -3,
    ]);

    UserSession::query()->create([
        'id' => 'session-123',
        'user_id' => $player->getKey(),
        'ip_address' => '203.0.113.10',
        'user_agent' => 'Mozilla/5.0',
        'last_activity_at' => Carbon::now()->subMinutes(5),
        'expires_at' => Carbon::now()->addHours(2),
    ]);

    LoginActivity::factory()->for($player)->create([
        'ip_address' => '203.0.113.10',
        'ip_address_hash' => hash('sha256', '203.0.113.10'),
        'user_agent' => 'Mozilla/5.0',
        'logged_at' => Carbon::now()->subMinutes(10),
        'via_sitter' => false,
    ]);

    LoginActivity::factory()->for($player)->create([
        'ip_address' => '198.51.100.22',
        'ip_address_hash' => hash('sha256', '198.51.100.22'),
        'user_agent' => 'Chrome',
        'logged_at' => Carbon::now()->subMinutes(30),
        'via_sitter' => true,
        'acting_sitter_id' => $admin->getKey(),
    ]);

    MovementOrder::query()->create([
        'user_id' => $player->getKey(),
        'origin_village_id' => $homeVillage->getKey(),
        'target_village_id' => $outpostVillage->getKey(),
        'movement_type' => 'attack',
        'mission' => 'raid',
        'status' => 'en route',
        'depart_at' => Carbon::now()->subMinutes(25),
        'arrive_at' => Carbon::now()->addMinutes(15),
        'return_at' => Carbon::now()->addHours(2),
    ]);

    $component = Livewire::test(PlayerAudit::class)
        ->set('lookup', 'audit-target')
        ->call('lookupPlayer');

    $playerData = $component->get('player');
    $villages = $component->get('villages');
    $ipAddresses = $component->get('ipAddresses');
    $report = $component->get('auditReport');

    expect($playerData)->not->toBeNull();
    expect($playerData['username'] ?? null)->toBe('audit-target');
    expect($villages)->toHaveCount(2);
    expect($ipAddresses)->not->toBeEmpty();
    expect($report)->toBeString()
        ->toContain('audit-target')
        ->toContain('Villages (2)')
        ->toContain('Sessions (1)')
        ->toContain('Recent movements');
});

it('requires selecting a player when multiple matches exist', function (): void {
    $admin = User::factory()->create([
        'legacy_uid' => User::LEGACY_ADMIN_UID,
    ]);

    actingAs($admin);
    actingAs($admin, 'admin');

    $first = User::factory()->create(['username' => 'gaul-rider']);
    $second = User::factory()->create(['username' => 'gaul-chief']);

    $component = Livewire::test(PlayerAudit::class)
        ->set('lookup', 'gaul')
        ->call('lookupPlayer');

    $matches = $component->get('matches');

    expect($matches)->toHaveCount(2);
    expect(collect($matches)->pluck('username'))->toContain('gaul-chief')->toContain('gaul-rider');
    expect($component->get('auditReport'))->toBeNull();
});
