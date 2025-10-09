<?php

namespace Tests\Feature\Auth;

use App\Services\Auth\LegacyLoginResult;
use App\Services\Auth\LegacyLoginService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\LegacyLoginHarness;
use Tests\TestCase;

class LegacyLoginParityTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('scenarioProvider')]
    public function test_modern_service_matches_legacy_behaviour(callable $scenarioFactory): void
    {
        $scenario = $scenarioFactory();
        $service = $this->app->make(LegacyLoginService::class);
        $harness = new LegacyLoginHarness();

        $legacy = $harness->attempt($scenario['identifier'], $scenario['password']);
        $modern = $service->attempt($scenario['identifier'], $scenario['password']);

        if (($legacy['mode'] ?? null) === null) {
            $this->assertNull($modern);

            return;
        }

        $this->assertInstanceOf(LegacyLoginResult::class, $modern);
        $this->assertSame($scenario['expected']['mode'], $modern->mode);
        $this->assertSame($scenario['expected']['user_id'], optional($modern->user)->getKey());
        $this->assertSame($scenario['expected']['sitter_id'], optional($modern->sitter)->getKey());
        $this->assertSame($scenario['expected']['activation_id'], optional($modern->activation)->getKey());
    }

    public static function scenarioProvider(): iterable
    {
        $scenarios = require __DIR__.'/../../Support/legacy_login_scenarios.php';

        foreach ($scenarios as $name => $factory) {
            yield $name => [$factory];
        }
    }
}
