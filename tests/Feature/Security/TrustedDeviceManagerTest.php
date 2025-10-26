<?php

declare(strict_types=1);

use App\Models\TrustedDevice;
use App\Models\User;
use App\Services\Security\TrustedDeviceManager;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function fakeTrustedRequest(array $server = []): Request
{
    $defaults = [
        'REMOTE_ADDR' => '198.51.100.42',
        'HTTP_USER_AGENT' => 'Travian Test Agent/1.0',
    ];

    return Request::create('/', 'GET', [], [], [], array_merge($defaults, $server));
}

beforeEach(function (): void {
    Config::set('security.trusted_devices.enabled', true);
    Config::set('security.trusted_devices.cookie.name', 'travian_trusted_device');
    Config::set('security.trusted_devices.cookie.lifetime_days', 180);
    Config::set('security.trusted_devices.default_expiration_days', 180);
    app('cookie')->flushQueuedCookies();
});

it('issues a trusted device and queues a cookie', function (): void {
    $user = User::factory()->create();
    $request = fakeTrustedRequest();

    /** @var TrustedDeviceManager $manager */
    $manager = app(TrustedDeviceManager::class);

    $device = $manager->rememberCurrentDevice($user, $request, 'QA Laptop');

    expect($device)
        ->user_id->toEqual($user->getKey())
        ->label->toEqual('QA Laptop')
        ->token_hash->not->toBeEmpty();

    $queued = app('cookie')->queued($manager->cookieName());
    expect($queued)->not->toBeNull();

    $payload = json_decode(base64_decode($queued->getValue(), true) ?: '', true, flags: JSON_THROW_ON_ERROR);

    expect($payload)
        ->toBeArray()
        ->toHaveKeys(['id', 'token']);

    expect(TrustedDevice::query()->forUser($user)->count())->toBe(1);
});

it('resolves a trusted device from cookie and refreshes last used timestamp', function (): void {
    $user = User::factory()->create();
    $firstRequest = fakeTrustedRequest([
        'REMOTE_ADDR' => '203.0.113.10',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (TestBrowser)',
    ]);

    /** @var TrustedDeviceManager $manager */
    $manager = app(TrustedDeviceManager::class);

    $device = $manager->rememberCurrentDevice($user, $firstRequest, 'Staging desktop');

    $queuedCookie = app('cookie')->queued($manager->cookieName());
    expect($queuedCookie)->not->toBeNull();

    $secondRequest = fakeTrustedRequest([
        'REMOTE_ADDR' => '203.0.113.20',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (OtherBrowser)',
    ]);
    $secondRequest->cookies->set($manager->cookieName(), $queuedCookie->getValue());

    Carbon::setTestNow(now()->addMinutes(5));

    $resolved = $manager->resolveCurrentDevice($user, $secondRequest);

    expect($resolved)
        ->not->toBeNull()
        ->public_id->toEqual($device->public_id);

    $refreshed = $resolved->fresh();

    expect($refreshed->last_used_at)->not->toBeNull();
    expect($refreshed->last_used_at)->toBeInstanceOf(Carbon::class);
    expect($refreshed->last_used_at?->diffInSeconds(now()))->toBeLessThan(2);

    Carbon::setTestNow();
});

it('revokes a trusted device and clears cookie for the current request', function (): void {
    $user = User::factory()->create();
    $request = fakeTrustedRequest();

    /** @var TrustedDeviceManager $manager */
    $manager = app(TrustedDeviceManager::class);

    $device = $manager->rememberCurrentDevice($user, $request, null);
    $queued = app('cookie')->queued($manager->cookieName());

    $assertRequest = fakeTrustedRequest();
    $assertRequest->cookies->set($manager->cookieName(), $queued->getValue());
    app()->instance('request', $assertRequest);

    expect($manager->revokeDevice($user, $device->public_id))->toBeTrue();
    expect($device->fresh()->revoked_at)->not->toBeNull();

    $cleared = app('cookie')->queued($manager->cookieName());
    expect($cleared)->not->toBeNull();
    expect($cleared->getExpiresTime())->toBeLessThan(time());
});
