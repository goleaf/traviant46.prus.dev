<?php

declare(strict_types=1);

use App\Actions\Fortify\CreateNewUser;
use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    foreach ([
        'village_buildings',
        'resource_fields',
        'villages',
        'building_types',
        'users',
        'password_reset_tokens',
        'sessions',
    ] as $table) {
        Schema::dropIfExists($table);
    }

    $migrations = [
        'database/migrations/0001_01_01_000000_create_users_table.php',
        'database/migrations/2025_10_15_000200_add_role_to_users_table.php',
        'database/migrations/2025_10_09_174049_add_two_factor_columns_to_users_table.php',
        'database/migrations/2025_10_10_000000_add_ban_columns_to_users_table.php',
        'database/migrations/2025_03_01_000000_create_villages_table.php',
        'database/migrations/2025_10_26_212609_update_villages_table_structure.php',
        'database/migrations/2025_03_01_000005_create_building_types_table.php',
        'database/migrations/2025_03_01_000010_create_village_buildings_table.php',
        'database/migrations/2025_03_01_000011_add_buildable_columns_to_village_buildings.php',
        'database/migrations/2025_10_26_223333_create_resource_fields_table.php',
    ];

    foreach ($migrations as $path) {
        $migration = require base_path($path);
        $migration->up();
    }
});

it('skips reserved legacy uids when registering new players', function (): void {
    User::factory()->create(['legacy_uid' => User::LEGACY_ADMIN_UID]);
    User::factory()->create(['legacy_uid' => 1]);

    $existingMax = (int) User::query()->max('legacy_uid');

    app(CreateNewUser::class)->create([
        'username' => 'newplayer',
        'name' => 'New Player',
        'email' => 'player@example.com',
        'password' => 'Password1!YZ',
        'password_confirmation' => 'Password1!YZ',
    ]);

    $user = User::query()->where('email', 'player@example.com')->firstOrFail();

    $expectedLegacyUid = $existingMax + 1;

    while (User::isReservedLegacyUid($expectedLegacyUid)) {
        $expectedLegacyUid++;
    }

    expect($user->legacy_uid)->toBe($expectedLegacyUid)
        ->and(User::reservedLegacyUids())->not->toContain((int) $user->legacy_uid);
});

it('creates a starter village for new players', function (): void {
    /** @var User $user */
    $user = app(CreateNewUser::class)->create([
        'username' => 'starter_player',
        'name' => 'Starter Player',
        'email' => 'starter@example.com',
        'password' => 'Password1!YZ',
        'password_confirmation' => 'Password1!YZ',
    ]);

    $user = $user->fresh();

    $village = Village::query()->where('user_id', $user->getKey())->firstOrFail();

    expect($village->x_coordinate)->toBe(0)
        ->and($village->y_coordinate)->toBe(0)
        ->and((bool) $village->is_capital)->toBeTrue()
        ->and($village->village_category)->toBe('capital')
        ->and($village->population)->toBe(2);

    $resourceFields = DB::table('resource_fields')
        ->where('village_id', $village->getKey())
        ->get();

    expect($resourceFields)->toHaveCount(18);

    expect($resourceFields->where('kind', '!=', 'crop')->pluck('level')->unique()->values()->all())
        ->toBe([0]);
    expect($resourceFields->where('kind', 'crop')->pluck('level')->unique()->values()->all())
        ->toBe([1]);

    $buildings = DB::table('village_buildings')
        ->where('village_id', $village->getKey())
        ->get()
        ->keyBy('building_type');

    expect($buildings->keys()->sort()->values()->all())
        ->toBe([1, 10, 11, 16]);

    foreach ([1, 10, 11, 16] as $buildingType) {
        expect((int) $buildings->get($buildingType)->level)->toBe(1);
    }
});
