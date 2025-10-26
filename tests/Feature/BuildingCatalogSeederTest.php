<?php

declare(strict_types=1);

use App\Models\Game\BuildingCatalog;
use App\Models\Game\BuildingType;
use Database\Seeders\BuildingCatalogSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\artisan;

it('seeds the building catalog with production bonuses and storage capacities', function (): void {
    Schema::dropIfExists('building_catalog');
    Schema::dropIfExists('building_types');

    Schema::create('building_types', function (Blueprint $table): void {
        $table->id();
        $table->unsignedInteger('gid')->nullable()->unique();
        $table->string('slug')->unique();
        $table->string('name');
        $table->string('category')->nullable();
        $table->unsignedTinyInteger('max_level')->nullable();
        $table->json('metadata')->nullable();
        $table->timestamps();
        $table->index('category');
    });

    Schema::create('building_catalog', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('building_type_id')->unique()->nullable();
        $table->string('building_slug')->unique();
        $table->string('name');
        $table->json('prerequisites')->nullable();
        $table->json('bonuses_per_level')->nullable();
        $table->json('storage_capacity_per_level')->nullable();
        $table->timestamps();
    });

    $definitions = [
        'sawmill' => ['gid' => 5, 'name' => 'Sawmill', 'max_level' => 5],
        'grain-mill' => ['gid' => 8, 'name' => 'Grain Mill', 'max_level' => 5],
        'iron-foundry' => ['gid' => 7, 'name' => 'Iron Foundry', 'max_level' => 5],
        'bakery' => ['gid' => 9, 'name' => 'Bakery', 'max_level' => 5],
        'warehouse' => ['gid' => 10, 'name' => 'Warehouse', 'max_level' => 20],
        'granary' => ['gid' => 11, 'name' => 'Granary', 'max_level' => 20],
    ];

    foreach ($definitions as $slug => $attributes) {
        BuildingType::query()->create([
            'gid' => $attributes['gid'],
            'slug' => $slug,
            'name' => $attributes['name'],
            'category' => 'economy',
            'max_level' => $attributes['max_level'],
            'metadata' => ['description' => $attributes['name'].' building type'],
        ]);
    }

    artisan('db:seed', [
        '--class' => BuildingCatalogSeeder::class,
        '--no-interaction' => true,
    ]);

    expect(BuildingCatalog::query()->count())->toBe(count($definitions));

    $sawmill = BuildingCatalog::query()->firstWhere('building_slug', 'sawmill');
    expect($sawmill)->not->toBeNull();
    expect($sawmill->building_type_id)->toBe(
        BuildingType::query()->firstWhere('slug', 'sawmill')?->getKey(),
    );
    expect($sawmill->bonuses_per_level)->toMatchArray([
        1 => 5,
        2 => 10,
        3 => 15,
        4 => 20,
        5 => 25,
    ]);
    expect($sawmill->storage_capacity_per_level)->toBeNull();

    $sawmillPrerequisites = collect($sawmill->prerequisites);
    expect($sawmillPrerequisites->firstWhere('slug', 'woodcutter')['level'] ?? null)->toBe(10);
    expect($sawmillPrerequisites->firstWhere('slug', 'main-building')['level'] ?? null)->toBe(5);

    $warehouse = BuildingCatalog::query()->firstWhere('building_slug', 'warehouse');
    expect($warehouse)->not->toBeNull();
    expect($warehouse->storage_capacity_per_level)->toMatchArray([
        1 => 1200,
        2 => 1700,
        3 => 2300,
        4 => 3100,
        5 => 4000,
        6 => 5000,
        7 => 6300,
        8 => 7800,
        9 => 9600,
        10 => 11800,
        11 => 14400,
        12 => 17600,
        13 => 21400,
        14 => 25900,
        15 => 31300,
        16 => 37900,
        17 => 45700,
        18 => 55100,
        19 => 66400,
        20 => 80000,
    ]);
    expect($warehouse->bonuses_per_level)->toBeNull();

    $granary = BuildingCatalog::query()->firstWhere('building_slug', 'granary');
    expect($granary)->not->toBeNull();
    expect($granary->storage_capacity_per_level)->toMatchArray($warehouse->storage_capacity_per_level);
    $granaryPrerequisites = collect($granary->prerequisites);
    expect($granaryPrerequisites->firstWhere('slug', 'main-building')['level'] ?? null)->toBe(1);

    Schema::dropIfExists('building_catalog');
    Schema::dropIfExists('building_types');
});
