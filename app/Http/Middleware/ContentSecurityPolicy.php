<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Vite;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = $this->resolveNonce();

        Vite::useCspNonce($nonce);
        Livewire::useScriptTagAttributes([
            'nonce' => $nonce,
        ]);
        App::instance('csp.nonce', $nonce);

        $response = $next($request);

        $csp = $this->buildPolicy($request, $nonce);

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');

        return $response;
    }

    private function resolveNonce(): string
    {
        /** @var string|null $nonce */
        $nonce = App::has('csp.nonce') ? App::get('csp.nonce') : null;

        if (is_string($nonce) && $nonce !== '') {
            return $nonce;
        }

        return bin2hex(random_bytes(16));
    }

    private function buildPolicy(Request $request, string $nonce): string
    {
        $scriptSources = [
            "'self'",
            "'nonce-{$nonce}'",
        ];

        $styleSources = [
            "'self'",
            "'nonce-{$nonce}'",
            'https://fonts.bunny.net',
        ];

        $connectSources = [
            "'self'",
        ];

        $fontSources = [
            "'self'",
            'https://fonts.bunny.net',
            'data:',
        ];

        $imgSources = [
            "'self'",
            'data:',
            'blob:',
        ];

        if (Config::get('app.asset_url')) {
            $assetHost = rtrim((string) Config::get('app.asset_url'), '/');

            $scriptSources[] = $assetHost;
            $styleSources[] = $assetHost;
            $connectSources[] = $assetHost;
        }

        if (App::environment('local')) {
            $scriptSources[] = 'http://localhost:5173';
            $scriptSources[] = 'http://127.0.0.1:5173';
            $styleSources[] = 'http://localhost:5173';
            $styleSources[] = 'http://127.0.0.1:5173';
            $connectSources[] = 'http://localhost:5173';
            $connectSources[] = 'http://127.0.0.1:5173';
            $connectSources[] = 'ws://localhost:5173';
            $connectSources[] = 'ws://127.0.0.1:5173';
        }

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => array_unique($scriptSources),
            'style-src' => array_unique($styleSources),
            'img-src' => array_unique($imgSources),
            'font-src' => array_unique($fontSources),
            'connect-src' => array_unique($connectSources),
            'object-src' => ["'none'"],
            'frame-ancestors' => ["'self'"],
            'form-action' => ["'self'"],
            'base-uri' => ["'self'"],
            'manifest-src' => ["'self'"],
        ];

        return collect($directives)
            ->map(fn (array $sources, string $directive) => $directive.' '.implode(' ', $sources))
            ->implode('; ');
    }
}
