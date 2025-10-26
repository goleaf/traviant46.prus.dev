<?php

declare(strict_types=1);

use App\Actions\Game\ApplyStarvationAction;
use App\Models\Game\MovementOrder;
use App\Models\Game\ReinforcementGarrison;
use App\Models\Game\TroopType;
use App\Models\Game\Village;
use App\Models\Game\VillageUnit;
use App\Models\Report;
use App\Models\ReportRecipient;
use App\Models\User;
use App\Support\Travian\UnitCatalog;
use Carbon\Carbon;
use Database\Seeders\TroopTypeSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    seed(TroopTypeSeeder::class);
    Carbon::setTestNow(Carbon::parse('2025-05-15 12:00:00'));

    Schema::disableForeignKeyConstraints();
    Schema::dropIfExists('reports');
    Schema::enableForeignKeyConstraints();

    Schema::create('reports', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->unsignedBigInteger('origin_village_id')->nullable();
        $table->unsignedBigInteger('target_village_id')->nullable();
        $table->string('report_type', 64);
        $table->string('category', 32)->nullable();
        $table->string('delivery_scope', 32)->default('personal');
        $table->boolean('is_system_generated')->default(false);
        $table->boolean('is_persistent')->default(false);
        $table->unsignedTinyInteger('loss_percentage')->nullable();
        $table->json('payload')->nullable();
        $table->json('bounty')->nullable();
        $table->timestamp('triggered_at')->nullable();
        $table->timestamp('viewed_at')->nullable();
        $table->timestamp('archived_at')->nullable();
        $table->timestamp('deleted_at')->nullable();
        $table->json('metadata')->nullable();
        $table->timestamps();
    });
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('prioritises foreign reinforcements when resolving starvation', function (): void {
    $catalog = app(UnitCatalog::class);

    $owner = User::factory()->create(['race' => 1, 'tribe' => 1]);
    $watcher = User::factory()->create(['race' => 3, 'tribe' => 3]);

    $village = Village::factory()->create([
        'user_id' => $owner->getKey(),
        'watcher_user_id' => $watcher->getKey(),
        'population' => 18,
        'production' => [
            'crop' => 32,
        ],
        'resource_balances' => [
            'wood' => 0,
            'clay' => 0,
            'iron' => 0,
            'crop' => -80,
        ],
        'storage' => [
            'granary' => 8_000,
            'granary_empty_eta' => Carbon::now()->subMinutes(10)->toIso8601String(),
        ],
    ]);

    $legionnaire = TroopType::query()->where('code', 'romans-legionnaire')->firstOrFail();

    VillageUnit::query()->create([
        'village_id' => $village->getKey(),
        'unit_type_id' => $legionnaire->getKey(),
        'quantity' => 10,
    ]);

    $foreign = ReinforcementGarrison::query()->create([
        'stationed_village_id' => $village->getKey(),
        'unit_composition' => ['u1' => 28],
        'upkeep' => $catalog->calculateCompositionUpkeep(2, ['u1' => 28]),
        'is_active' => true,
        'metadata' => ['race' => 2],
    ]);

    $local = ReinforcementGarrison::query()->create([
        'stationed_village_id' => $village->getKey(),
        'owner_user_id' => $owner->getKey(),
        'unit_composition' => ['u1' => 15],
        'upkeep' => $catalog->calculateCompositionUpkeep(1, ['u1' => 15]),
        'is_active' => true,
        'metadata' => ['race' => 1],
    ]);

    app(ApplyStarvationAction::class)->execute($village->fresh());

    $village->refresh();
    $foreign->refresh();
    $local->refresh();

    expect($village->resource_balances['crop'])->toBeGreaterThanOrEqual(0);
    expect(data_get($village->storage, 'granary_empty_eta'))->toBeNull();
    expect(data_get($village->storage, 'granary_empty_at'))->toBeNull();

    expect(data_get($foreign->unit_composition, 'u1'))->toBeLessThan(28);
    expect(data_get($local->unit_composition, 'u1', 0))->toBeLessThan(15);

    $report = Report::query()->where('report_type', 'system.starvation')->first();
    expect($report)->not->toBeNull();
    expect($report->payload['killed'][0]['category'])->toBe('foreign_reinforcement');

    $recipients = ReportRecipient::query()->pluck('recipient_id')->all();
    expect($recipients)->toContain($owner->getKey());
    expect($recipients)->toContain($watcher->getKey());
});

it('starves home units and movements when reinforcements are insufficient', function (): void {
    $owner = User::factory()->create(['race' => 1, 'tribe' => 1]);

    $village = Village::factory()->create([
        'user_id' => $owner->getKey(),
        'population' => 12,
        'production' => [
            'crop' => 22,
        ],
        'resource_balances' => [
            'wood' => 0,
            'clay' => 0,
            'iron' => 0,
            'crop' => -60,
        ],
        'storage' => [
            'granary' => 6_000,
            'granary_empty_eta' => Carbon::now()->subMinutes(5)->toIso8601String(),
        ],
    ]);

    $legionnaire = TroopType::query()->where('code', 'romans-legionnaire')->firstOrFail();

    $villageUnit = VillageUnit::query()->create([
        'village_id' => $village->getKey(),
        'unit_type_id' => $legionnaire->getKey(),
        'quantity' => 10,
    ]);

    $movement = MovementOrder::query()->create([
        'user_id' => $owner->getKey(),
        'origin_village_id' => $village->getKey(),
        'target_village_id' => $village->getKey(),
        'movement_type' => 'reinforcement',
        'status' => 'in_transit',
        'depart_at' => Carbon::now()->subMinutes(20),
        'arrive_at' => Carbon::now()->addMinutes(15),
        'payload' => [
            'units' => ['u1' => 12],
        ],
        'metadata' => [
            'mode' => 0,
        ],
    ]);

    app(ApplyStarvationAction::class)->execute($village->fresh());

    $village->refresh();
    $villageUnit->refresh();
    $movement->refresh();

    expect($village->resource_balances['crop'])->toBeGreaterThanOrEqual(0);
    expect(data_get($movement->payload, 'units.u1', 0))->toBeLessThan(12);
    expect($villageUnit->quantity)->toBeLessThan(10);

    $report = Report::query()->where('report_type', 'system.starvation')->latest('id')->first();
    expect($report)->not->toBeNull();

    $categories = collect($report->payload['killed'])->pluck('category')->all();
    expect($categories)->toContain('home_garrison');
    expect($categories)->toContain('movement');

    $upkeepRecovered = $report->payload['recovered_upkeep_per_hour'] ?? 0;
    expect($upkeepRecovered)->toBeGreaterThan(0);
});
