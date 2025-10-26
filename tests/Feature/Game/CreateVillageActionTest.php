<?php

declare(strict_types=1);

use App\Actions\Game\CreateVillageAction;
use App\Data\Game\VillageCreationData;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Database\Seeders\TroopTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    seed(TroopTypeSeeder::class);
});

it('creates a starter village with default blueprint attributes', function (): void {
    $world = World::factory()->create();
    $user = User::factory()->create(['username' => 'starter_player']);

    /** @var CreateVillageAction $action */
    $action = app(CreateVillageAction::class);

    $result = $action->execute(0, 0, $user, $world);

    expect($result)->toBeInstanceOf(VillageCreationData::class);

    $village = $result->village;

    expect($village)->toBeInstanceOf(Village::class)
        ->and($village->user_id)->toBe($user->getKey())
        ->and($village->is_capital)->toBeTrue()
        ->and($village->village_category)->toBe('capital')
        ->and($village->population)->toBe(2)
        ->and($village->resource_balances)->toMatchArray([
            'wood' => 750,
            'clay' => 750,
            'iron' => 750,
            'crop' => 750,
        ])
        ->and($village->storage)->toMatchArray([
            'wood' => 800,
            'clay' => 800,
            'iron' => 800,
            'crop' => 800,
            'warehouse' => 800,
            'granary' => 800,
            'extra' => ['warehouse' => 0, 'granary' => 0],
        ]);

    expect($result->resourceFields)->toHaveCount(18);

    $kinds = $result->resourceFields
        ->groupBy('kind')
        ->map->count()
        ->all();

    expect($kinds)->toMatchArray([
        'wood' => 4,
        'clay' => 4,
        'iron' => 4,
        'crop' => 6,
    ]);

    $cropLevels = $result->resourceFields
        ->where('kind', 'crop')
        ->pluck('level')
        ->unique()
        ->all();

    expect($cropLevels)->toBe([1]);

    $otherLevels = $result->resourceFields
        ->reject(fn ($field) => $field->kind === 'crop')
        ->pluck('level')
        ->unique()
        ->all();

    expect($otherLevels)->toBe([0]);

    $buildingTypes = $result->infrastructure
        ->sortBy('slot_number')
        ->pluck('building_type')
        ->values()
        ->all();

    expect($buildingTypes)->toBe([1, 10, 11, 16]);

    expect($result->infrastructure->pluck('level')->unique()->all())->toBe([1]);

    expect($result->garrison)->toHaveCount(10);
    expect($result->garrison->pluck('quantity')->unique()->all())->toBe([0]);
});

it('creates additional villages as non-capitals with configurable field levels', function (): void {
    Config::set('travian.settings.game.otherVillageCreationFieldsLevel', 2);

    $world = World::factory()->create();
    $user = User::factory()->create(['username' => 'expander']);

    /** @var CreateVillageAction $action */
    $action = app(CreateVillageAction::class);

    $action->execute(5, -5, $user, $world);

    $second = $action->execute(12, 3, $user, $world);

    expect($second->village->is_capital)->toBeFalse()
        ->and($second->village->village_category)->toBe('normal');

    $levels = $second->resourceFields->pluck('level')->unique()->all();
    expect($levels)->toBe([2]);

    Config::set('travian.settings.game.otherVillageCreationFieldsLevel', 0);
});
