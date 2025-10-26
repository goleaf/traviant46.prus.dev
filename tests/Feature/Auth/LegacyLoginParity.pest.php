<?php

declare(strict_types=1);

use App\Services\Auth\LegacyLoginResult;
use App\Services\Auth\LegacyLoginService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\LegacyLoginHarness;

uses(RefreshDatabase::class);

dataset('legacy-login-scenarios', function () {
    return require __DIR__.'/../../Support/legacy_login_scenarios.php';
});

it('matches the legacy login harness', function (callable $scenarioFactory) {
    $scenario = $scenarioFactory();
    $service = app(LegacyLoginService::class);
    $harness = new LegacyLoginHarness;

    $legacy = $harness->attempt($scenario['identifier'], $scenario['password']);
    $modern = $service->attempt($scenario['identifier'], $scenario['password']);

    if (($legacy['mode'] ?? null) === null) {
        expect($modern)->toBeNull();

        return;
    }

    expect($modern)->toBeInstanceOf(LegacyLoginResult::class)
        ->and($modern->mode)->toBe($scenario['expected']['mode'])
        ->and(optional($modern->user)->getKey())->toBe($scenario['expected']['user_id'])
        ->and(optional($modern->sitter)->getKey())->toBe($scenario['expected']['sitter_id'])
        ->and(optional($modern->activation)->getKey())->toBe($scenario['expected']['activation_id']);
})->with('legacy-login-scenarios');
