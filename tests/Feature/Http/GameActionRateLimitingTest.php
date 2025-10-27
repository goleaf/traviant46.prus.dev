<?php

declare(strict_types=1);

use App\Http\Middleware\ThrottleGameEndpoints;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Psr\Log\NullLogger;

beforeEach(function (): void {
    Log::swap(new NullLogger);
});

function simulateLimiterHit(string $limiter, Request $request): void
{
    $limits = collect(RateLimiter::limiter($limiter)($request));

    $limits->each(function ($limit) use ($limiter): void {
        $hashedKey = md5($limiter.$limit->key);
        RateLimiter::hit($hashedKey, $limit->decaySeconds);
    });
}

it('enforces per-user throttles for marketplace actions with json payloads', function (): void {
    config()->set('security.rate_limits.market.per_minute', 1);
    config()->set('security.rate_limits.market.per_hour', 2);

    $user = tap(new User, static fn (User $model) => $model->setAttribute('id', 321));

    $request = Request::create('/market/sell', 'POST', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
    $request->setUserResolver(static fn () => $user);
    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    RateLimiter::clear(md5('game.market'.'minute:user:321'));
    RateLimiter::clear(md5('game.market'.'hour:user:321'));

    simulateLimiterHit('game.market', $request);

    $limits = collect(RateLimiter::limiter('game.market')($request));
    $response = $limits->firstWhere('key', 'minute:user:321')->responseCallback;

    $json = $response();

    expect($json->getStatusCode())->toBe(429)
        ->and($json->getData(true)['meta']['wait_seconds'])->toBeGreaterThanOrEqual(1);
});

it('enforces per-user throttles for send actions', function (): void {
    config()->set('security.rate_limits.send.per_minute', 1);
    config()->set('security.rate_limits.send.per_hour', 2);

    $user = tap(new User, static fn (User $model) => $model->setAttribute('id', 654));

    $request = Request::create('/send', 'POST', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
    $request->setUserResolver(static fn () => $user);
    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    RateLimiter::clear(md5('game.send'.'minute:user:654'));
    RateLimiter::clear(md5('game.send'.'hour:user:654'));

    simulateLimiterHit('game.send', $request);

    $limits = collect(RateLimiter::limiter('game.send')($request));
    $response = $limits->firstWhere('key', 'minute:user:654')->responseCallback;

    $json = $response();

    expect($json->getStatusCode())->toBe(429)
        ->and($json->getData(true)['meta']['wait_seconds'])->toBeGreaterThanOrEqual(1);
});

it('enforces per-user throttles for messaging actions', function (): void {
    config()->set('security.rate_limits.messages.per_minute', 1);
    config()->set('security.rate_limits.messages.per_hour', 2);

    $user = tap(new User, static fn (User $model) => $model->setAttribute('id', 987));

    $request = Request::create('/messages', 'POST', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
    $request->setUserResolver(static fn () => $user);
    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    RateLimiter::clear(md5('game.messages'.'minute:user:987'));
    RateLimiter::clear(md5('game.messages'.'hour:user:987'));

    simulateLimiterHit('game.messages', $request);

    $limits = collect(RateLimiter::limiter('game.messages')($request));
    $response = $limits->firstWhere('key', 'minute:user:987')->responseCallback;

    $json = $response();

    expect($json->getStatusCode())->toBe(429)
        ->and($json->getData(true)['meta']['wait_seconds'])->toBeGreaterThanOrEqual(1);
});

/**
 * Ensure the middleware maps nested marketplace routes onto the marketplace limiter.
 */
it('throttles nested market routes via middleware', function (): void {
    config()->set('security.rate_limits.market.per_minute', 1);
    config()->set('security.rate_limits.market.per_hour', 2);

    $user = tap(new User, static fn (User $model) => $model->setAttribute('id', 432));

    $request = Request::create('/villages/12/market', 'POST', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
    $request->setUserResolver(static fn () => $user);
    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    RateLimiter::clear(md5('game.market'.'minute:user:432'));
    RateLimiter::clear(md5('game.market'.'hour:user:432'));

    simulateLimiterHit('game.market', $request);

    $middleware = new ThrottleGameEndpoints(app(ThrottleRequests::class));

    $response = $middleware->handle($request, static function () {
        return response()->json(['status' => 'ok']);
    });

    expect($response->getStatusCode())->toBe(429)
        ->and($response->getData(true)['meta']['wait_seconds'])->toBeGreaterThanOrEqual(1);
});

/**
 * Confirm rally point send flows are throttled when routed through the middleware.
 */
it('throttles rally point send routes via middleware', function (): void {
    config()->set('security.rate_limits.send.per_minute', 1);
    config()->set('security.rate_limits.send.per_hour', 2);

    $user = tap(new User, static fn (User $model) => $model->setAttribute('id', 543));

    $request = Request::create('/villages/34/rally-point/send', 'POST', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
    $request->setUserResolver(static fn () => $user);
    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    RateLimiter::clear(md5('game.send'.'minute:user:543'));
    RateLimiter::clear(md5('game.send'.'hour:user:543'));

    simulateLimiterHit('game.send', $request);

    $middleware = new ThrottleGameEndpoints(app(ThrottleRequests::class));

    $response = $middleware->handle($request, static function () {
        return response()->json(['status' => 'ok']);
    });

    expect($response->getStatusCode())->toBe(429)
        ->and($response->getData(true)['meta']['wait_seconds'])->toBeGreaterThanOrEqual(1);
});

/**
 * Validate Livewire message payloads honour the messaging limiter.
 */
it('throttles livewire messaging payloads via middleware', function (): void {
    config()->set('security.rate_limits.messages.per_minute', 1);
    config()->set('security.rate_limits.messages.per_hour', 2);

    $user = tap(new User, static fn (User $model) => $model->setAttribute('id', 654));

    $request = Request::create('/livewire/message/game.messages', 'POST', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
    $request->setUserResolver(static fn () => $user);
    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    RateLimiter::clear(md5('game.messages'.'minute:user:654'));
    RateLimiter::clear(md5('game.messages'.'hour:user:654'));

    simulateLimiterHit('game.messages', $request);

    $middleware = new ThrottleGameEndpoints(app(ThrottleRequests::class));

    $response = $middleware->handle($request, static function () {
        return response()->json(['status' => 'ok']);
    });

    expect($response->getStatusCode())->toBe(429)
        ->and($response->getData(true)['meta']['wait_seconds'])->toBeGreaterThanOrEqual(1);
});
