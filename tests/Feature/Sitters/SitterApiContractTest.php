<?php

declare(strict_types=1);

use App\Enums\SitterPermission;
use App\Models\SitterDelegation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

it('returns sitter listings that satisfy the documented contract', function (): void {
    $owner = User::factory()->create();
    $sitter = User::factory()->create();

    SitterDelegation::query()->create([
        'owner_user_id' => $owner->getKey(),
        'sitter_user_id' => $sitter->getKey(),
        'permissions' => [
            SitterPermission::SendTroops->key(),
            SitterPermission::SendResources->key(),
        ],
        'expires_at' => Carbon::now()->addDay(),
        'created_by' => $owner->getKey(),
        'updated_by' => $owner->getKey(),
    ]);

    actingAs($owner);

    $response = getJson('/api/v1/sitters');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'sitter' => ['id', 'username', 'name'],
                    'permissions',
                    'bitmask',
                    'preset',
                    'preset_label',
                    'expires_at',
                    'created_at',
                    'updated_at',
                ],
            ],
            'available_permissions' => [
                ['key', 'label', 'bitmask'],
            ],
            'presets' => [
                ['value', 'label', 'description', 'permissions'],
            ],
        ]);
});
