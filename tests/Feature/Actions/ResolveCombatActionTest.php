<?php

declare(strict_types=1);

use App\Actions\Game\ResolveCombatAction;
use App\Models\Game\Movement;
use App\Models\Game\Unit;
use App\Models\Game\Village;
use App\Models\Report;
use App\Models\User;
use App\Services\Game\MovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('database.connections.legacy', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);

    DB::purge('legacy');

    createLegacyMovementSchema();
    createLegacyUnitsSchema();
    createLegacyEnforcementSchema();
    prepareReportSchema();
});

it('resolves a normal attack with building and wall damage', function (): void {
    $attacker = User::factory()->create(['legacy_uid' => 111, 'race' => 1]);
    $defender = User::factory()->create(['legacy_uid' => 222, 'race' => 1]);

    $originVillage = Village::factory()->create([
        'legacy_kid' => 7001,
        'user_id' => $attacker->getKey(),
        'population' => 200,
    ]);

    $targetVillage = Village::factory()->create([
        'legacy_kid' => 8001,
        'user_id' => $defender->getKey(),
        'population' => 60,
        'defense_bonus' => ['wall_level' => 4, 'wall' => 40],
    ]);

    DB::table('buildings')->insert([
        'village_id' => $targetVillage->getKey(),
        'building_type' => 31,
        'position' => 7,
        'level' => 12,
    ]);

    Unit::query()->create([
        'kid' => $targetVillage->legacy_kid,
        'race' => 1,
        'u1' => 40,
        'u2' => 15,
    ]);

    DB::connection('legacy')->table('enforcement')->insert([
        'uid' => $defender->legacy_uid,
        'kid' => $targetVillage->legacy_kid,
        'to_kid' => $targetVillage->legacy_kid,
        'race' => 1,
        'pop' => 30,
        'u2' => 20,
        'u3' => 10,
    ]);

    $movement = Movement::query()->create([
        'kid' => $originVillage->legacy_kid,
        'to_kid' => $targetVillage->legacy_kid,
        'race' => 1,
        'ctar1' => 7,
        'ctar2' => 0,
        'spyType' => 0,
        'redeployHero' => false,
        'mode' => MovementService::SORTTYPE_GOING,
        'attack_type' => MovementService::ATTACKTYPE_NORMAL,
        'start_time' => 1_700_000_000,
        'end_time' => 1_700_000_600,
        'data' => json_encode(['seed' => 98765], JSON_THROW_ON_ERROR),
        'markState' => 0,
        'proc' => 0,
        'u1' => 120,
        'u3' => 40,
        'u7' => 12,
        'u8' => 16,
    ]);

    $movement->forceFill(['uid' => $attacker->legacy_uid])->save();

    expect(User::query()->where('legacy_uid', $movement->uid)->value('id'))->toBe($attacker->getKey());

    $action = app(ResolveCombatAction::class);
    $action->execute([
        $movement->to_kid => [
            $movement->end_time => [$movement],
        ],
    ]);

    $movement->refresh();
    expect($movement->proc)->toBe(1);
    expect($movement->u1)->toBeGreaterThanOrEqual(0);
    expect($movement->u7)->toBeGreaterThanOrEqual(0);
    expect($movement->u8)->toBeGreaterThanOrEqual(0);

    $attackReport = Report::query()->where('category', 'attack')->first();
    expect($attackReport)->not->toBeNull();
    expect($attackReport->payload['defenders']['losses']['u1'])->toBeGreaterThan(0);
    expect($attackReport->payload['morale_modifier'])->toBe(1.25);
    expect($attackReport->payload['wall']['bonus'])->toBe(40.0);

    $updatedGarrison = Unit::query()->find($targetVillage->legacy_kid);
    $remainingReinforcements = DB::connection('legacy')
        ->table('enforcement')
        ->where('to_kid', $targetVillage->legacy_kid)
        ->first();

    $defenderSurvivors = $attackReport->payload['defenders']['survivors'];
    expect($defenderSurvivors['u1'])->toBe((int) $updatedGarrison->u1);
    $reinforcementSurvivors = (int) ($remainingReinforcements?->u2 ?? 0) + (int) ($remainingReinforcements?->u3 ?? 0);
    expect($defenderSurvivors['u2'] + $defenderSurvivors['u3'])->toBe((int) $updatedGarrison->u2 + $reinforcementSurvivors);

    $wallLevel = $targetVillage->fresh()->defense_bonus['wall_level'] ?? null;
    expect($wallLevel)->toBeLessThan(4);

    $buildingLevel = DB::table('buildings')
        ->where('village_id', $targetVillage->getKey())
        ->where('position', 7)
        ->value('level');
    expect((int) $buildingLevel)->toBeLessThan(12);

    expect(Report::query()->count())->toBeGreaterThanOrEqual(2);
    expect($attackReport->payload['outcome'])->toBe('victory');

    $movementData = json_decode((string) $movement->data, true, 512, JSON_THROW_ON_ERROR);
    expect($movementData['battle']['morale_modifier'])->toBe(1.25);
    expect($movementData['battle']['wall_bonus'])->toBe(40.0);
});

it('handles failed raids without structural damage', function (): void {
    $attacker = User::factory()->create(['legacy_uid' => 333, 'race' => 1]);
    $defender = User::factory()->create(['legacy_uid' => 444, 'race' => 1]);

    $originVillage = Village::factory()->create([
        'legacy_kid' => 9001,
        'user_id' => $attacker->getKey(),
        'population' => 120,
    ]);

    $targetVillage = Village::factory()->create([
        'legacy_kid' => 9002,
        'user_id' => $defender->getKey(),
        'population' => 300,
        'defense_bonus' => ['wall_level' => 3, 'wall' => 30],
    ]);

    Unit::query()->create([
        'kid' => $targetVillage->legacy_kid,
        'race' => 1,
        'u2' => 100,
        'u3' => 80,
    ]);

    $movement = Movement::query()->create([
        'uid' => $attacker->legacy_uid,
        'kid' => $originVillage->legacy_kid,
        'to_kid' => $targetVillage->legacy_kid,
        'race' => 1,
        'ctar1' => 0,
        'ctar2' => 0,
        'spyType' => 0,
        'redeployHero' => false,
        'mode' => MovementService::SORTTYPE_GOING,
        'attack_type' => MovementService::ATTACKTYPE_RAID,
        'start_time' => 1_700_100_000,
        'end_time' => 1_700_100_600,
        'data' => json_encode(['seed' => 45678], JSON_THROW_ON_ERROR),
        'markState' => 0,
        'proc' => 0,
        'u1' => 25,
        'u3' => 10,
    ]);

    $action = app(ResolveCombatAction::class);
    $action->execute([
        $movement->to_kid => [
            $movement->end_time => [$movement],
        ],
    ]);

    $movement->refresh();
    expect($movement->proc)->toBe(1);
    expect($movement->u1 + $movement->u3)->toBe(0);

    $updatedGarrison = Unit::query()->find($targetVillage->legacy_kid);
    expect($updatedGarrison->u2)->toBeLessThanOrEqual(100);
    expect($updatedGarrison->u3)->toBeLessThanOrEqual(80);

    $reports = Report::query()->get();
    expect($reports)->not->toBeEmpty();
    $defenseReport = Report::query()->where('category', 'defense')->first();
    expect($defenseReport)->not->toBeNull();
    expect($defenseReport->payload['outcome'])->toBe('victory');
});

it('prevents structural damage during raids even with siege units', function (): void {
    $attacker = User::factory()->create(['legacy_uid' => 555, 'race' => 1]);
    $defender = User::factory()->create(['legacy_uid' => 666, 'race' => 1]);

    $originVillage = Village::factory()->create([
        'legacy_kid' => 9501,
        'user_id' => $attacker->getKey(),
        'population' => 150,
    ]);

    $targetVillage = Village::factory()->create([
        'legacy_kid' => 9502,
        'user_id' => $defender->getKey(),
        'population' => 150,
        'defense_bonus' => ['wall_level' => 5, 'wall' => 50],
    ]);

    DB::table('buildings')->insert([
        'village_id' => $targetVillage->getKey(),
        'building_type' => 15,
        'position' => 7,
        'level' => 10,
    ]);

    Unit::query()->create([
        'kid' => $targetVillage->legacy_kid,
        'race' => 1,
    ]);

    $movement = Movement::query()->create([
        'kid' => $originVillage->legacy_kid,
        'to_kid' => $targetVillage->legacy_kid,
        'race' => 1,
        'ctar1' => 7,
        'ctar2' => 0,
        'spyType' => 0,
        'redeployHero' => false,
        'mode' => MovementService::SORTTYPE_GOING,
        'attack_type' => MovementService::ATTACKTYPE_RAID,
        'start_time' => 1_700_200_000,
        'end_time' => 1_700_200_600,
        'data' => json_encode(['seed' => 11223], JSON_THROW_ON_ERROR),
        'markState' => 0,
        'proc' => 0,
        'u1' => 60,
        'u3' => 30,
        'u7' => 15,
        'u8' => 20,
    ]);

    $movement->forceFill(['uid' => $attacker->legacy_uid])->save();

    $initialWallLevel = $targetVillage->defense_bonus['wall_level'];
    $initialBuildingLevel = 10;

    $action = app(ResolveCombatAction::class);
    $action->execute([
        $movement->to_kid => [
            $movement->end_time => [$movement],
        ],
    ]);

    $movement->refresh();
    expect($movement->proc)->toBe(1);
    expect($movement->u1 + $movement->u3 + $movement->u7 + $movement->u8)->toBeGreaterThan(0);

    $wallLevel = $targetVillage->fresh()->defense_bonus['wall_level'] ?? null;
    expect($wallLevel)->toBe($initialWallLevel);

    $buildingLevel = DB::table('buildings')
        ->where('village_id', $targetVillage->getKey())
        ->where('position', 7)
        ->value('level');
    expect((int) $buildingLevel)->toBe($initialBuildingLevel);

    $attackReport = Report::query()->where('category', 'attack')->first();
    expect($attackReport)->not->toBeNull();
    expect($attackReport->payload['building_damage'])->toBe([]);
    expect($attackReport->payload['wall']['before'])->toBe($attackReport->payload['wall']['after']);
});

function createLegacyMovementSchema(): void
{
    Schema::connection('legacy')->dropIfExists('movement');
    Schema::connection('legacy')->create('movement', function ($table): void {
        $table->increments('id');
        $table->integer('uid')->nullable();
        $table->integer('kid')->nullable();
        $table->integer('to_kid')->nullable();
        $table->tinyInteger('mode')->default(0);
        $table->tinyInteger('attack_type')->default(0);
        $table->tinyInteger('spyType')->default(0);
        $table->tinyInteger('ctar1')->default(0);
        $table->tinyInteger('ctar2')->default(0);
        $table->boolean('redeployHero')->default(false);
        $table->integer('race')->default(1);
        $table->integer('start_time')->nullable();
        $table->integer('end_time')->nullable();
        $table->integer('return_time')->nullable();
        $table->text('data')->nullable();
        $table->integer('markState')->default(0);
        $table->integer('proc')->default(0);
        foreach (range(1, 11) as $slot) {
            $table->integer('u'.$slot)->default(0);
        }
    });
}

function createLegacyUnitsSchema(): void
{
    Schema::connection('legacy')->dropIfExists('units');
    Schema::connection('legacy')->create('units', function ($table): void {
        $table->integer('kid')->primary();
        $table->tinyInteger('race')->default(1);
        foreach (range(1, 11) as $slot) {
            $table->integer('u'.$slot)->default(0);
        }
        $table->integer('u99')->default(0);
    });
}

function createLegacyEnforcementSchema(): void
{
    Schema::connection('legacy')->dropIfExists('enforcement');
    Schema::connection('legacy')->create('enforcement', function ($table): void {
        $table->increments('id');
        $table->integer('uid')->nullable();
        $table->integer('kid')->nullable();
        $table->integer('to_kid')->nullable();
        $table->tinyInteger('race')->nullable();
        $table->integer('pop')->nullable();
        foreach (range(1, 11) as $slot) {
            $table->integer('u'.$slot)->default(0);
        }
    });
}

function prepareReportSchema(): void
{
    Schema::dropIfExists('report_recipients');
    Schema::dropIfExists('reports');

    Schema::create('reports', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('legacy_report_id')->nullable();
        $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('alliance_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('origin_village_id')->nullable()->constrained('villages')->nullOnDelete();
        $table->foreignId('target_village_id')->nullable()->constrained('villages')->nullOnDelete();
        $table->string('report_type');
        $table->string('category')->nullable();
        $table->string('delivery_scope')->nullable();
        $table->boolean('is_system_generated')->default(false);
        $table->boolean('is_persistent')->default(false);
        $table->unsignedTinyInteger('loss_percentage')->nullable();
        $table->json('payload')->nullable();
        $table->json('bounty')->nullable();
        $table->json('metadata')->nullable();
        $table->timestamp('triggered_at')->nullable();
        $table->timestamp('viewed_at')->nullable();
        $table->timestamp('archived_at')->nullable();
        $table->timestamp('deleted_at')->nullable();
        $table->timestamps();
    });

    Schema::create('report_recipients', function ($table): void {
        $table->id();
        $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
        $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
        $table->unsignedBigInteger('recipient_alliance_id')->nullable();
        $table->string('visibility_scope', 32)->default('owner');
        $table->string('status', 24)->default('unread');
        $table->boolean('is_flagged')->default(false);
        $table->timestamp('viewed_at')->nullable();
        $table->timestamp('archived_at')->nullable();
        $table->timestamp('deleted_at')->nullable();
        $table->timestamp('forwarded_at')->nullable();
        $table->string('share_token', 32)->nullable();
        $table->json('metadata')->nullable();
        $table->timestamps();
        $table->unique(['report_id', 'recipient_id']);
        $table->index(['recipient_id', 'status']);
    });
}
