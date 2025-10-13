<?php

namespace Tests\Unit\Policies;

use App\Enums\SitterPermission;
use App\Models\Game\Village;
use App\Models\User;
use App\Policies\VillagePolicy;
use App\Services\Auth\SessionContextManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VillagePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_manage_village(): void
    {
        $owner = User::factory()->create();
        $village = Village::factory()->create([
            'owner_id' => $owner->getKey(),
        ]);

        $policy = app(VillagePolicy::class);

        $this->assertTrue($policy->view($owner, $village));
        $this->assertTrue($policy->update($owner, $village));
    }

    public function test_sitter_with_permissions_can_update_village(): void
    {
        $owner = User::factory()->create();
        $sitter = User::factory()->create();
        $village = Village::factory()->create([
            'owner_id' => $owner->getKey(),
        ]);

        app(SessionContextManager::class)->enterSitterContext($owner, $sitter, [
            'permissions' => [SitterPermission::MANAGE_VILLAGE, SitterPermission::SWITCH_VILLAGE],
            'assignment_source' => 'delegated',
        ]);

        $policy = app(VillagePolicy::class);

        $this->assertTrue($policy->view($sitter, $village));
        $this->assertTrue($policy->update($sitter, $village));
        $this->assertTrue($policy->switch($sitter, $village));
    }

    public function test_sitter_without_permissions_cannot_update_village(): void
    {
        $owner = User::factory()->create();
        $sitter = User::factory()->create();
        $village = Village::factory()->create([
            'owner_id' => $owner->getKey(),
        ]);

        app(SessionContextManager::class)->enterSitterContext($owner, $sitter, [
            'permissions' => [SitterPermission::VIEW_VILLAGE],
            'assignment_source' => 'delegated',
        ]);

        $policy = app(VillagePolicy::class);

        $this->assertTrue($policy->view($sitter, $village));
        $this->assertFalse($policy->update($sitter, $village));
    }
}
