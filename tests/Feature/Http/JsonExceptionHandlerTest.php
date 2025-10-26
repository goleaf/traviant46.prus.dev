<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    if (! Route::has('testing.throttle')) {
        Route::middleware('throttle:1,1')->group(function (): void {
            Route::get('/testing/throttle', fn () => response()->json(['status' => 'ok']))->name('testing.throttle');
        });

        Route::getRoutes()->refreshNameLookups();
    }

    if (! Route::has('testing.http-exception')) {
        Route::get('/testing/http-exception', function (): never {
            abort(403, 'Forbidden Action');
        })->name('testing.http-exception');

        Route::getRoutes()->refreshNameLookups();
    }

    Config::set('game.maintenance.enabled', false);
    Config::set('game.maintenance.allowed_legacy_uids', []);
    Config::set('game.start_time', null);
});

it('returns structured validation errors with details for JSON requests', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/sitters', []);

    $response->assertUnprocessable()
        ->assertJson([
            'status' => 'error',
            'code' => 422,
        ]);

    expect($response->json('message'))->toBe('The given data was invalid.')
        ->and($response->json('errors'))->toBeArray()
        ->and($response->json('errors.sitter_username.0'))->toBeString();
});

it('returns structured throttle responses with retry metadata', function (): void {
    $this->getJson(route('testing.throttle'))->assertOk();

    $response = $this->getJson(route('testing.throttle'));

    $response->assertStatus(429)
        ->assertJson([
            'status' => 'error',
            'code' => 429,
        ]);

    expect($response->json('message'))->toBeString()
        ->and($response->json('meta.retry_after'))->not->toBeNull();
});

it('formats http exceptions into the standard error structure', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson(route('testing.http-exception'));

    $response->assertForbidden()
        ->assertJson([
            'status' => 'error',
            'code' => 403,
            'message' => 'Forbidden Action',
        ]);
});

it('returns structured not found responses for missing routes', function (): void {
    $response = $this->getJson('/testing/missing-route');

    $response->assertNotFound()
        ->assertJson([
            'status' => 'error',
            'code' => 404,
            'message' => 'Not Found',
        ]);

    expect($response->json('code'))->toBe(404);
});
