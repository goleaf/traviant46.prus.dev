<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Sentry\State\Scope;
use Symfony\Component\HttpFoundation\Response;

class InjectRequestContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = config('monitoring.request_id_header', 'X-Request-Id');
        $incomingRequestId = $request->headers->get($header) ?: (string) Str::uuid();
        $request->headers->set($header, $incomingRequestId);

        $user = $request->user();

        return Context::scope(function () use ($request, $next, $header, $incomingRequestId, $user) {
            Context::add(array_filter([
                'request_id' => $incomingRequestId,
                'request_method' => $request->method(),
                'request_path' => '/'.trim($request->path(), '/'),
                'client_ip' => $request->ip(),
                'user_id' => $user?->getAuthIdentifier(),
            ], static fn ($value) => $value !== null && $value !== ''));

            if (class_exists(Scope::class)) {
                \Sentry\configureScope(function (Scope $scope) use ($incomingRequestId, $user): void {
                    $scope->setTag('request_id', $incomingRequestId);

                    if ($user !== null) {
                        $scope->setUser([
                            'id' => $user->getAuthIdentifier(),
                            'email' => $user->email ?? null,
                            'username' => $user->username ?? null,
                        ]);
                    }
                });
            }

            if (class_exists(Bugsnag::class) && app()->bound('bugsnag')) {
                Bugsnag::setMetaData([
                    'request' => [
                        'request_id' => $incomingRequestId,
                        'method' => $request->method(),
                        'path' => '/'.trim($request->path(), '/'),
                        'ip' => $request->ip(),
                    ],
                ]);
            }

            /** @var Response $response */
            $response = $next($request);

            $response->headers->set($header, $incomingRequestId);

            return $response;
        });
    }
}
