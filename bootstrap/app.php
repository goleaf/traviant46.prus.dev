<?php

declare(strict_types=1);

use App\Http\Kernel as HttpKernel;
use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Support\Http\ProblemDetails;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        AppServiceProvider::class,
        AuthServiceProvider::class,
        EventServiceProvider::class,
        FortifyServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        HttpKernel::register($middleware);

        $trustedProxyConfig = config('security.trusted_proxies', []);
        $presetIdentifiers = array_map('strtolower', $trustedProxyConfig['presets'] ?? []);
        $proxyRanges = $trustedProxyConfig['ranges'] ?? [];

        $trustedProxies = $trustedProxyConfig['ips'] ?? [];

        foreach ($presetIdentifiers as $preset) {
            if (isset($proxyRanges[$preset])) {
                $trustedProxies = array_merge($trustedProxies, $proxyRanges[$preset]);
            }
        }

        $trustedProxies = array_values(array_unique(array_filter(
            $trustedProxies,
            static fn ($ip) => $ip !== null && $ip !== '',
        )));

        $headerPreference = strtolower((string) ($trustedProxyConfig['headers'] ?? 'forwarded'));

        if (in_array('aws_elb', $presetIdentifiers, true) && $headerPreference === 'forwarded') {
            $headerPreference = 'aws';
        }

        $forwardedAll = SymfonyRequest::HEADER_X_FORWARDED_FOR
            | SymfonyRequest::HEADER_X_FORWARDED_HOST
            | SymfonyRequest::HEADER_X_FORWARDED_PROTO
            | SymfonyRequest::HEADER_X_FORWARDED_PORT
            | SymfonyRequest::HEADER_X_FORWARDED_PREFIX;

        $forwardedNone = defined(SymfonyRequest::class.'::HEADER_X_FORWARDED_NONE')
            ? SymfonyRequest::HEADER_X_FORWARDED_NONE
            : 0;

        $headerMap = [
            'forwarded' => SymfonyRequest::HEADER_FORWARDED,
            'x-forwarded' => $forwardedAll,
            'aws' => SymfonyRequest::HEADER_X_FORWARDED_AWS_ELB,
            'cloudflare' => $forwardedAll,
            'none' => $forwardedNone,
        ];

        $middleware->trustProxies(
            at: $trustedProxies === [] ? null : $trustedProxies,
            headers: $headerMap[$headerPreference] ?? $forwardedAll,
        );

        $middleware->use([
            HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $throwable, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return ProblemDetails::fromException($throwable, $request)->toResponse();
        });
    })->create();
