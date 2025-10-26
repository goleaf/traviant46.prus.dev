<?php

declare(strict_types=1);

use App\Http\Kernel as HttpKernel;
use App\Models\LoginActivity;
use App\Models\MultiAccountAlert;
use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\FortifyServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

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
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('model:prune', ['--model' => LoginActivity::class])
            ->dailyAt('02:10');

        $schedule->command('model:prune', ['--model' => MultiAccountAlert::class])
            ->dailyAt('02:30');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(static function (Request $request, Throwable $throwable): bool {
            return $request->expectsJson() || $request->is('api/*');
        });

        $formatError = static function (
            string $message,
            int $status,
            ?array $errors = null,
            array $meta = []
        ): JsonResponse {
            $payload = [
                'status' => 'error',
                'code' => $status,
                'message' => $message,
            ];

            if (! empty($errors)) {
                $payload['errors'] = $errors;
            }

            if (! empty($meta)) {
                $payload['meta'] = $meta;
            }

            return response()->json($payload, $status);
        };

        $exceptions->render(function (AuthenticationException $exception, Request $request) use ($formatError) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            $message = $exception->getMessage() !== '' ? $exception->getMessage() : 'Unauthenticated.';

            return $formatError($message, SymfonyResponse::HTTP_UNAUTHORIZED);
        });

        $exceptions->render(function (ValidationException $exception, Request $request) use ($formatError) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            $message = $exception->getMessage() !== '' ? $exception->getMessage() : 'The given data was invalid.';

            return $formatError(
                $message,
                SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY,
                $exception->errors(),
            );
        });

        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) use ($formatError) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            $headers = $exception->getHeaders();

            $retryAfter = $headers['Retry-After'] ?? $headers['retry-after'] ?? null;
            $limit = $headers['X-RateLimit-Limit'] ?? $headers['x-ratelimit-limit'] ?? null;
            $remaining = $headers['X-RateLimit-Remaining'] ?? $headers['x-ratelimit-remaining'] ?? null;

            $meta = array_filter([
                'retry_after' => is_numeric($retryAfter) ? (int) $retryAfter : $retryAfter,
                'limit' => is_numeric($limit) ? (int) $limit : $limit,
                'remaining' => is_numeric($remaining) ? (int) $remaining : $remaining,
            ], static fn ($value) => $value !== null);

            return $formatError(
                $exception->getMessage() !== '' ? $exception->getMessage() : 'Too many requests.',
                SymfonyResponse::HTTP_TOO_MANY_REQUESTS,
                null,
                $meta,
            )->withHeaders($headers);
        });

        $exceptions->respond(function (SymfonyResponse $response, Throwable $exception, Request $request) use ($formatError) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return $response;
            }

            if ($response instanceof JsonResponse) {
                return $response;
            }

            $status = $response->getStatusCode();
            $debug = (bool) config('app.debug');
            $message = trim((string) $exception->getMessage());

            if ($status >= SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR && ! $debug) {
                $message = 'An unexpected error occurred.';
            } elseif ($message === '') {
                $message = match (true) {
                    $exception instanceof HttpExceptionInterface => SymfonyResponse::$statusTexts[$status] ?? 'HTTP Error',
                    default => SymfonyResponse::$statusTexts[$status] ?? 'An error occurred.',
                };
            }

            return $formatError($message, $status)->withHeaders($response->headers->all());
        });
    })->create();
