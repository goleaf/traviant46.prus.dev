<?php

declare(strict_types=1);

it('applies CSP with nonce to the authentication views', function (): void {
    $response = $this->get(route('login'));

    $response->assertOk();

    $policy = $response->headers->get('Content-Security-Policy');

    expect($policy)->not->toBeNull();
    expect($policy)->toContain("default-src 'self'");
    expect($policy)->toContain("script-src 'self' 'nonce-");

    preg_match("/script-src 'self' 'nonce-([^']+)'/", $policy, $matches);

    expect($matches[1] ?? null)->not->toBeNull();

    $nonce = $matches[1];

    expect($response->getContent())->toContain('nonce="'.$nonce.'"');
});

it('sets additional security headers on authentication responses', function (): void {
    $response = $this->get(route('login'));

    $response->assertOk();

    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'DENY');
});
