<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Game\Building;
use App\Models\Game\MovementOrder;
use App\Models\Game\Village;
use App\Models\Game\VillageBuilding;
use App\Models\Game\VillageBuildingUpgrade;
use App\Models\Game\VillageResource;
use App\Models\Game\VillageUnit;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VillageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(VillageBuilding::class)) {
            eval(<<<'PHP'
                namespace App\Models\Game;

                use Illuminate\Database\Eloquent\Factories\HasFactory;
                use Illuminate\Database\Eloquent\Model;

                class VillageBuilding extends Model
                {
                    use HasFactory;

                    protected $table = 'village_buildings';

                    protected $fillable = [
                        'village_id',
                        'slot_number',
                        'building_type',
                        'level',
                    ];
                }
            PHP);
        }

        if (! Schema::hasColumn('villages', 'production')) {
            Schema::table('villages', function (Blueprint $table) {
                $table->json('production')->nullable();
            });
        }

        if (! Schema::hasColumn('villages', 'deleted_at')) {
            Schema::table('villages', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('village_resources')) {
            Schema::create('village_resources', function (Blueprint $table) {
                $table->id();
                $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
                $table->string('resource_type');
                $table->unsignedTinyInteger('level')->default(0);
                $table->integer('production_per_hour')->default(0);
                $table->integer('storage_capacity')->default(0);
                $table->json('bonuses')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('buildings')) {
            Schema::create('buildings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
                $table->unsignedTinyInteger('slot_number');
                $table->unsignedSmallInteger('building_type')->nullable();
                $table->string('buildable_type')->nullable();
                $table->unsignedBigInteger('buildable_id')->nullable();
                $table->unsignedTinyInteger('level')->default(0);
                $table->timestamps();
                $table->unique(['village_id', 'slot_number']);
            });
        }

        if (! Schema::hasTable('movement_orders')) {
            Schema::create('movement_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('origin_village_id')->constrained('villages')->cascadeOnDelete();
                $table->foreignId('target_village_id')->constrained('villages')->cascadeOnDelete();
                $table->string('movement_type');
                $table->string('status');
                $table->timestamp('depart_at')->nullable();
                $table->timestamp('arrive_at')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }

        Village::resolveRelationUsing('buildingUpgrades', function (Village $village) {
            return $village->hasMany(VillageBuildingUpgrade::class);
        });

        Village::resolveRelationUsing('buildings', function (Village $village) {
            return $village->hasMany(VillageBuilding::class);
        });
    }

    public function test_relationships_load_related_models(): void
    {
        $user = User::factory()->create();

        $village = Village::factory()->create(['population' => 120, 'production' => ['wood' => 50]]);

        $building = Building::query()->create([
            'village_id' => $village->id,
            'slot_number' => 3,
            'building_type' => 19,
            'level' => 2,
        ]);

        $unit = VillageUnit::query()->create([
            'village_id' => $village->id,
            'unit_type_id' => 1,
            'quantity' => 25,
        ]);

        $resource = VillageResource::query()->create([
            'village_id' => $village->id,
            'resource_type' => 'wood',
            'level' => 5,
            'production_per_hour' => 30,
            'storage_capacity' => 800,
            'bonuses' => ['percent' => 10],
        ]);

        $movement = MovementOrder::query()->create([
            'user_id' => $user->id,
            'origin_village_id' => $village->id,
            'target_village_id' => $village->id,
            'movement_type' => 'raid',
            'status' => 'queued',
            'depart_at' => Carbon::now(),
            'arrive_at' => Carbon::now()->addHour(),
            'payload' => ['units' => []],
        ]);

        $village->load('buildings', 'units', 'resources', 'movements');
        $this->assertTrue($village->buildings->first()->is($building));
        $this->assertTrue($village->units->first()->is($unit));
        $this->assertTrue($village->resources->first()->is($resource));
        $this->assertTrue($village->movements->first()->is($movement));
    }

    public function test_accessors_combine_coordinates_population_and_production(): void
    {
        $village = Village::factory()->create([
            'x_coordinate' => -123,
            'y_coordinate' => 45,
            'population' => 200,
            'production' => ['wood' => 100, 'clay' => 90],
        ]);

        VillageUnit::query()->create([
            'village_id' => $village->id,
            'unit_type_id' => 2,
            'quantity' => 50,
        ]);

        VillageResource::query()->create([
            'village_id' => $village->id,
            'resource_type' => 'clay',
            'level' => 4,
            'production_per_hour' => 40,
            'storage_capacity' => 700,
            'bonuses' => [],
        ]);

        VillageResource::query()->create([
            'village_id' => $village->id,
            'resource_type' => 'crop',
            'level' => 3,
            'production_per_hour' => 25,
            'storage_capacity' => 600,
            'bonuses' => [],
        ]);

        $fresh = $village->fresh();

        $this->assertSame(['x' => -123, 'y' => 45], $fresh->coordinates);
        $this->assertSame(250, $fresh->total_population);
        $this->assertSame([
            'wood' => 100,
            'clay' => 130,
            'crop' => 25,
        ], $fresh->production_rates);
    }

    public function test_scopes_filter_villages(): void
    {
        $origin = Village::factory()->create([
            'x_coordinate' => 0,
            'y_coordinate' => 0,
            'is_capital' => true,
        ]);

        $near = Village::factory()->create([
            'x_coordinate' => 3,
            'y_coordinate' => 4,
            'is_capital' => false,
        ]);

        $far = Village::factory()->create([
            'x_coordinate' => 10,
            'y_coordinate' => 10,
            'is_capital' => false,
        ]);

        $this->assertTrue(Village::capital()->pluck('id')->contains($origin->id));
        $this->assertFalse(Village::capital()->pluck('id')->contains($near->id));

        $byCoordinates = Village::byCoordinates(3, 4)->first();
        $this->assertNotNull($byCoordinates);
        $this->assertTrue($byCoordinates->is($near));

        $inRadius = Village::inRadius(0, 0, 5)->get();
        $this->assertTrue($inRadius->contains($origin));
        $this->assertTrue($inRadius->contains($near));
        $this->assertFalse($inRadius->contains($far));
    }
}
