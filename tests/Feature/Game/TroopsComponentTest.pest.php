<?php

declare(strict_types=1);

use App\Livewire\Game\Troops;
use App\Models\Game\ReinforcementGarrison;
use App\Models\Game\TroopType;
use App\Models\Game\UnitTrainingBatch;
use App\Models\Game\Village;
use App\Models\Game\VillageUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('displays garrison summaries and training queue details', function (): void {
    Carbon::setTestNow('2025-03-01 12:00:00');

    $owner = User::factory()->create();
    $supporter = User::factory()->create();

    $village = Village::factory()->create(['user_id' => $owner->getKey()]);
    $allyVillage = Village::factory()->create(['user_id' => $supporter->getKey()]);

    $legionnaire = TroopType::factory()->create([
        'tribe' => 1,
        'code' => 'romans-legionnaire',
        'name' => 'Legionnaire',
        'upkeep' => 1,
    ]);

    $praetorian = TroopType::factory()->create([
        'tribe' => 1,
        'code' => 'romans-praetorian',
        'name' => 'Praetorian',
        'upkeep' => 1,
    ]);

    VillageUnit::factory()->create([
        'village_id' => $village->getKey(),
        'unit_type_id' => $legionnaire->getKey(),
        'quantity' => 180,
    ]);

    ReinforcementGarrison::query()->create([
        'legacy_enforcement_id' => 1001,
        'owner_user_id' => $supporter->getKey(),
        'home_village_id' => $allyVillage->getKey(),
        'stationed_village_id' => $village->getKey(),
        'unit_composition' => ['u1' => 40, 'u2' => 10],
        'upkeep' => 50,
        'is_active' => true,
        'deployed_at' => Carbon::now()->subHours(2),
        'metadata' => ['race' => 1],
    ]);

    UnitTrainingBatch::factory()->create([
        'village_id' => $village->getKey(),
        'unit_type_id' => $praetorian->getKey(),
        'quantity' => 25,
        'queue_position' => 0,
        'training_building' => 'barracks',
        'status' => \App\Enums\Game\UnitTrainingBatchStatus::Processing,
        'queued_at' => Carbon::now(),
        'starts_at' => Carbon::now(),
        'completes_at' => Carbon::now()->addMinutes(15),
    ]);

    $component = Livewire::actingAs($owner)->test(Troops::class, [
        'village' => $village,
    ]);

    $component
        ->assertSet('garrison.totals.owned', 180)
        ->assertSee('Legionnaire')
        ->assertSee(number_format(180))
        ->assertSee('Praetorian')
        ->assertSee('Processing')
        ->assertSet('queue.entries.0.quantity', 25)
        ->assertSee('Queued units: 25')
        ->assertSee('Active batches: 1')
        ->assertSee('Train');

    Carbon::setTestNow();
});

it('queues new training batches through the action', function (): void {
    Carbon::setTestNow('2025-03-02 09:15:00');

    $user = User::factory()->create();
    $village = Village::factory()->create(['user_id' => $user->getKey()]);

    $troopType = TroopType::factory()->create([
        'tribe' => 1,
        'code' => 'romans-legionnaire',
        'name' => 'Legionnaire',
        'upkeep' => 1,
    ]);

    VillageUnit::factory()->create([
        'village_id' => $village->getKey(),
        'unit_type_id' => $troopType->getKey(),
        'quantity' => 50,
    ]);

    $component = Livewire::actingAs($user)->test(Troops::class, [
        'village' => $village,
    ]);

    $component
        ->set('form.unit_type_id', $troopType->getKey())
        ->set('form.quantity', '30');

    expect($component->get('form')['quantity'])->toBe('30');

    $component->call('train');

    $errors = $component->errors()->messages();
    expect($errors)->toBe([]);

    expect(UnitTrainingBatch::query()->where('village_id', $village->getKey())->count())->toBe(1);

    $batch = UnitTrainingBatch::query()->first();
    expect($batch)->not->toBeNull();
    expect($batch?->quantity)->toBe(30);
    expect($batch?->unit_type_id)->toBe($troopType->getKey());

    $component
        ->assertSet('queue.entries.0.quantity', 30)
        ->assertSee('Queued units: 30')
        ->assertSee('Active batches: 1')
        ->assertSee('Train');

    Carbon::setTestNow();
});
