<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplySecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response->headers->has('Content-Security-Policy')) {
            $policy = $this->buildContentSecurityPolicy();

            if ($policy !== null) {
                $response->headers->set('Content-Security-Policy', $policy);
            }
        }

        if (! $response->headers->has('Referrer-Policy')) {
            $referrerPolicy = (string) config('security.headers.referrer-policy', '');

            if ($referrerPolicy !== '') {
                $response->headers->set('Referrer-Policy', $referrerPolicy);
            }
        }

        return $response;
    }

    private function buildContentSecurityPolicy(): ?string
    {
        $directives = $this->normalizeDirectiveConfig((array) config('security.headers.csp', []));

        if ($directives === []) {
            return null;
        }

        foreach ($this->environmentOverrides() as $directive => $values) {
            $existing = $directives[$directive] ?? [];
            $directives[$directive] = array_values(array_unique(array_merge($existing, $values)));
        }

        $policies = [];

        foreach ($directives as $directive => $values) {
            if ($values === []) {
                continue;
            }

            $policies[] = $directive.' '.implode(' ', $values);
        }

        return $policies === [] ? null : implode('; ', $policies);
    }

    /**
     * @param array<string, mixed> $directives
     * @return array<string, list<string>>
     */
    private function normalizeDirectiveConfig(array $directives): array
    {
        $normalized = [];

        foreach ($directives as $directive => $values) {
            $valueList = is_array($values) ? $values : [$values];

            $normalized[$directive] = array_values(array_filter(
                array_map(static fn ($value) => is_string($value) ? trim($value) : (string) $value, $valueList),
                static fn ($value) => $value !== '',
            ));
        }

        return $normalized;
    }

    /**
     * @return array<string, list<string>>
     */
    private function environmentOverrides(): array
    {
        $environment = app()->environment();
        $overrides = (array) config('security.headers.csp_environment_overrides', []);

        $matched = [];

        foreach ($overrides as $key => $values) {
            $environments = is_array($key) ? $key : explode(',', (string) $key);

            if (! in_array($environment, array_map('trim', $environments), true)) {
                continue;
            }

            foreach ((array) $values as $directive => $sources) {
                $current = $matched[$directive] ?? [];
                $sourceList = is_array($sources) ? $sources : [$sources];

                $matched[$directive] = array_values(array_filter(
                    array_map(static fn ($value) => is_string($value) ? trim($value) : (string) $value, array_merge($current, $sourceList)),
                    static fn ($value) => $value !== '',
                ));
            }
        }

        return $matched;
    }
}
