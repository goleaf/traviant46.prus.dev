<?php

namespace Tests\Feature\Auth;

use App\Enums\SitterPermission;
use App\Models\SitterAssignment;
use App\Models\User;
use App\Services\Auth\LegacyLoginResult;
use App\Services\Auth\LegacyLoginService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_delegated_sitter_includes_permissions(): void
    {
        $owner = User::factory()->create([
            'username' => 'delegated-owner',
            'email' => 'delegated-owner@example.com',
        ]);

        $sitterPassword = 'Delegate#9876';
        $sitter = User::factory()->create([
            'username' => 'delegated-sitter',
            'email' => 'delegated-sitter@example.com',
            'password' => Hash::make($sitterPassword),
        ]);

        SitterAssignment::updateOrCreate([
            'account_id' => $owner->getKey(),
            'sitter_id' => $sitter->getKey(),
        ], [
            'permissions' => [SitterPermission::MANAGE_VILLAGE, SitterPermission::SEND_TROOPS],
        ]);

        $service = $this->app->make(LegacyLoginService::class);

        $result = $service->attempt('delegated-owner', $sitterPassword);

        $this->assertInstanceOf(LegacyLoginResult::class, $result);
        $this->assertTrue($result->viaSitter());
        $this->assertContains(SitterPermission::MANAGE_VILLAGE, $result->context['permissions']);
        $this->assertSame('delegated', $result->context['assignment_source']);
    }
}
