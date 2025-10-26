<?php

declare(strict_types=1);

putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
putenv('CACHE_STORE=array');
putenv('SESSION_DRIVER=array');

$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = ':memory:';
$_ENV['CACHE_STORE'] = 'array';
$_ENV['SESSION_DRIVER'] = 'array';

$_SERVER['DB_CONNECTION'] = 'sqlite';
$_SERVER['DB_DATABASE'] = ':memory:';
$_SERVER['CACHE_STORE'] = 'array';
$_SERVER['SESSION_DRIVER'] = 'array';

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('session.driver', 'array');
    config()->set('game.start_time', null);
    config()->set('game.maintenance.enabled', false);
    config()->set('cache.default', 'array');
    Cache::setDefaultDriver('array');
    Cache::store('array')->forever('travian.world_config', []);
});

it('redirects players to the maintenance notice when maintenance mode is active', function (): void {
    config()->set('game.maintenance.enabled', true);

    $user = User::factory()->create();

    actingAs($user, config('fortify.guard'));

    $response = get(route('home'));

    $response->assertRedirect(route('game.maintenance'));
});

it('redirects banned players to the banned notice', function (): void {
    $user = User::factory()->create([
        'is_banned' => true,
    ]);

    actingAs($user, config('fortify.guard'));

    $response = get(route('home'));

    $response->assertRedirect(route('game.banned'));
});

it('redirects unverified players to the verification prompt', function (): void {
    $user = User::factory()->unverified()->create();

    actingAs($user, config('fortify.guard'));

    $response = get(route('home'));

    $response->assertRedirect(route('game.verify'));
});
