<?php

declare(strict_types=1);

use App\Events\Auth\LoginFailed;
use App\Events\Auth\LoginSucceeded;
use App\Events\Auth\UserLoggedOut;
use App\Jobs\SendAuthEventNotification;
use App\Listeners\DispatchAuthEventNotification;
use App\Models\User;
use App\Services\Security\AuthEventBroadcaster;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('registers listeners for auth lifecycle events', function (): void {
    Event::fake();

    Event::assertListening(LoginSucceeded::class, DispatchAuthEventNotification::class);
    Event::assertListening(LoginFailed::class, DispatchAuthEventNotification::class);
    Event::assertListening(UserLoggedOut::class, DispatchAuthEventNotification::class);
});

it('queues a webhook notification for successful logins', function (): void {
    Config::set('security.auth_events', [
        'enabled' => true,
        'driver' => 'webhook',
        'queue' => 'notifications',
        'webhook' => [
            'url' => 'https://example.com/hooks/auth',
            'secret' => 'secret',
            'timeout' => 3,
            'verify_ssl' => true,
        ],
        'eventbridge' => [],
    ]);

    $user = User::factory()->create();

    Bus::fake();

    app(DispatchAuthEventNotification::class)
        ->handle(new LoginSucceeded($user, 'web', '203.0.113.7', 'Browser', false, null));

    Bus::assertDispatched(SendAuthEventNotification::class, function (SendAuthEventNotification $job) {
        $reflection = new \ReflectionClass($job);

        $eventProperty = $reflection->getProperty('event');
        $eventProperty->setAccessible(true);

        $payloadProperty = $reflection->getProperty('payload');
        $payloadProperty->setAccessible(true);

        $payload = $payloadProperty->getValue($job);

        return $job->queue === 'notifications'
            && $eventProperty->getValue($job) === 'login.succeeded'
            && ($payload['ip_address'] ?? null) === '203.0.113.7';
    });
});

it('queues a webhook notification for failed logins', function (): void {
    Config::set('security.auth_events.enabled', true);
    Config::set('security.auth_events.driver', 'webhook');

    $user = User::factory()->create();

    Bus::fake();

    app(DispatchAuthEventNotification::class)->handle(new LoginFailed(
        $user,
        'warlord',
        'web',
        '198.51.100.10',
        'Browser',
        3,
        5,
        60,
    ));

    Bus::assertDispatched(SendAuthEventNotification::class, function (SendAuthEventNotification $job) {
        $reflection = new \ReflectionClass($job);

        $eventProperty = $reflection->getProperty('event');
        $eventProperty->setAccessible(true);

        $payloadProperty = $reflection->getProperty('payload');
        $payloadProperty->setAccessible(true);

        $payload = $payloadProperty->getValue($job);

        return $eventProperty->getValue($job) === 'login.failed'
            && ($payload['attempts'] ?? null) === 3
            && ($payload['max_attempts'] ?? null) === 5
            && ($payload['cooldown_seconds'] ?? null) === 60;
    });
});

it('queues a webhook notification for user logouts', function (): void {
    Config::set('security.auth_events.enabled', true);
    Config::set('security.auth_events.driver', 'webhook');

    $user = User::factory()->create();

    Bus::fake();

    app(DispatchAuthEventNotification::class)->handle(new UserLoggedOut(
        $user,
        'web',
        '198.51.100.20',
        'Browser',
    ));

    Bus::assertDispatched(SendAuthEventNotification::class, function (SendAuthEventNotification $job) {
        $reflection = new \ReflectionClass($job);

        $eventProperty = $reflection->getProperty('event');
        $eventProperty->setAccessible(true);

        return $eventProperty->getValue($job) === 'logout.completed';
    });
});

it('delivers webhook payloads with signatures', function (): void {
    Config::set('security.auth_events.enabled', true);
    Config::set('security.auth_events.driver', 'webhook');
    Config::set('security.auth_events.webhook.url', 'https://example.com/hooks/auth');
    Config::set('security.auth_events.webhook.secret', 'test-secret');

    Http::fake([
        'https://example.com/hooks/auth' => Http::response([], 200),
    ]);

    (new SendAuthEventNotification('login.succeeded', [
        'user' => ['id' => 1],
    ]))->handle(app(AuthEventBroadcaster::class));

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://example.com/hooks/auth'
            && $request->header('X-Auth-Event')[0] === 'login.succeeded'
            && isset($request->header('X-Auth-Event-Signature')[0])
            && $request->header('X-Auth-Event-Signature')[0] !== '';
    });
});
