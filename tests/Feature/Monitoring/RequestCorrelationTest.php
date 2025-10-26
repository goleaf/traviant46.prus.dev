<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

beforeAll(function (): void {
    Route::middleware('web')->get('/monitoring/request-correlation', fn () => response()->noContent());
});

it('attaches a request id header to responses', function (): void {
    $response = $this->get('/monitoring/request-correlation');

    $response->assertOk();
    $response->assertHeader('X-Request-Id');

    expect($response->headers->get('X-Request-Id'))
        ->not->toBeNull()
        ->and((string) $response->headers->get('X-Request-Id'))
        ->not->toBe('');
});

it('honours an incoming request id header', function (): void {
    $requestId = 'req-'.bin2hex(random_bytes(4));

    $response = $this
        ->withHeaders(['X-Request-Id' => $requestId])
        ->get('/monitoring/request-correlation');

    $response->assertOk();
    $response->assertHeader('X-Request-Id', $requestId);
});
