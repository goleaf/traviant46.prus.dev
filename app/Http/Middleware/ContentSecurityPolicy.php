<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if ($response->headers->has('Content-Security-Policy')) {
            return $response;
        }

        $csp = $this->buildPolicy();

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Content-Security-Policy', $csp);

        return $response;
    }

    protected function buildPolicy(): string
    {
        $default = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'"],
            'style-src' => ["'self'", 'https://fonts.bunny.net'],
            'font-src' => ["'self'", 'https://fonts.bunny.net'],
            'img-src' => ["'self'", 'data:'],
            'connect-src' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
            'base-uri' => ["'self'"],
        ];

        if (app()->environment('local')) {
            $default['script-src'][] = 'http://localhost:5173';
            $default['style-src'][] = 'http://localhost:5173';
            $default['connect-src'] = Arr::prepend($default['connect-src'], 'ws://localhost:5173');
            $default['connect-src'][] = 'http://localhost:5173';
        }

        return collect($default)
            ->map(fn(array $sources, string $directive) => $directive.' '.implode(' ', array_unique($sources)))
            ->implode('; ');
    }
}
