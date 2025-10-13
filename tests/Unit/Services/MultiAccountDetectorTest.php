<?php

namespace Tests\Unit\Services;

use App\Models\LoginActivity;
use App\Models\User;
use App\Services\Auth\SessionContextManager;
use App\Services\Security\MultiAccountDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiAccountDetectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_detector_flags_multi_account_alert_in_session(): void
    {
        $primary = User::factory()->create();
        $conflict = User::factory()->create();

        LoginActivity::create([
            'user_id' => $conflict->getKey(),
            'ip_address' => '203.0.113.1',
            'user_agent' => 'Test Agent',
            'via_sitter' => false,
        ]);

        $detector = app(MultiAccountDetector::class);
        $detector->record($primary, '203.0.113.1', now());

        $context = app(SessionContextManager::class)->multiAccountAlertContext();

        $this->assertSame($conflict->getKey(), $context['conflict_user_id']);
        $this->assertSame('203.0.113.1', $context['ip_address']);
        $this->assertNotEmpty($context['alert_id']);
    }
}
