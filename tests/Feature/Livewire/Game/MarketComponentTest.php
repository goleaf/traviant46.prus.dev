<?php

declare(strict_types=1);

use App\Livewire\Game\Market as GameMarket;
use App\Models\Game\MarketOffer;
use App\Models\Game\Trade;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use App\Services\Game\MarketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createVillageForUser(User $user, ?World $world = null, array $balances = []): Village
{
    $attributes = [
        'user_id' => $user->id,
        'resource_balances' => array_merge([
            'wood' => 10_000,
            'clay' => 10_000,
            'iron' => 10_000,
            'crop' => 10_000,
        ], $balances),
        'production' => [
            'wood' => 120,
            'clay' => 110,
            'iron' => 100,
            'crop' => 90,
        ],
    ];

    if (Schema::hasColumn('villages', 'world_id')) {
        $world ??= World::factory()->create([
            'speed' => 1.0,
        ]);

        $attributes['world_id'] = $world->id;
    }

    return Village::factory()->create($attributes);
}

it('posts a market offer and reserves merchants via Livewire', function (): void {
    $user = User::factory()->create(['race' => 1]);
    $village = createVillageForUser($user);

    $this->actingAs($user);

    Livewire::test(GameMarket::class, ['village' => $village])
        ->set('offerGive.wood', 600)
        ->set('offerWant.clay', 450)
        ->call('createOffer')
        ->assertSet('statusMessage', __('Offer posted. Merchants are now waiting for a partner.'));

    $offer = MarketOffer::query()->first();

    expect($offer)->not->toBeNull()
        ->and($offer->merchants)->toBe(2) // 600 wood / 500 cap => 2 merchants.
        ->and($offer->give)->toMatchArray(['wood' => 600])
        ->and($offer->want)->toMatchArray(['clay' => 450]);

    expect($village->fresh()->resource_balances['wood'])->toBe(10_000 - 600);
});

it('accepts an offer and schedules reciprocal trades', function (): void {
    $originUser = User::factory()->create(['race' => 1]);
    $acceptingUser = User::factory()->create(['race' => 2]);
    $world = World::factory()->create(['speed' => 1.0]);

    $originVillage = createVillageForUser($originUser, $world);
    $acceptingVillage = createVillageForUser($acceptingUser, $world);

    /** @var MarketService $marketService */
    $marketService = app(MarketService::class);

    $offer = $marketService->createOffer(
        $originVillage->fresh(),
        ['wood' => 800, 'clay' => 0, 'iron' => 0, 'crop' => 0],
        ['clay' => 650, 'wood' => 0, 'iron' => 0, 'crop' => 0],
    );

    $this->actingAs($acceptingUser);

    Livewire::test(GameMarket::class, ['village' => $acceptingVillage])
        ->call('acceptOffer', $offer->getKey())
        ->assertSet('statusMessage', __('Offer accepted. Merchants are en route.'));

    expect(MarketOffer::query()->count())->toBe(0);

    $trades = Trade::query()->get();

    expect($trades)->toHaveCount(2);

    $giveTrade = $trades->firstWhere('origin', $originVillage->getKey());
    $returnTrade = $trades->firstWhere('origin', $acceptingVillage->getKey());

    expect($giveTrade)->not->toBeNull()
        ->and(data_get($giveTrade->payload, 'context'))->toBe('offer:give')
        ->and(data_get($giveTrade->payload, 'resources.wood'))->toBe(800);

    expect($returnTrade)->not->toBeNull()
        ->and(data_get($returnTrade->payload, 'context'))->toBe('offer:want')
        ->and(data_get($returnTrade->payload, 'resources.clay'))->toBe(650);

    expect($acceptingVillage->fresh()->resource_balances['clay'])->toBe(10_000 - 650);
});

it('dispatches a direct trade shipment', function (): void {
    $user = User::factory()->create(['race' => 3]);
    $world = World::factory()->create(['speed' => 1.0]);

    $originVillage = createVillageForUser($user, $world);
    $targetVillage = createVillageForUser($user, $world);

    $this->actingAs($user);

    Livewire::test(GameMarket::class, ['village' => $originVillage])
        ->set('tradePayload.wood', 900)
        ->set('tradePayload.clay', 300)
        ->set('tradeTarget', (string) $targetVillage->getKey())
        ->call('sendTrade')
        ->assertSet('statusMessage', __('Merchants dispatched. Track their progress below.'));

    $trade = Trade::query()->where('origin', $originVillage->getKey())->first();

    expect($trade)->not->toBeNull()
        ->and(data_get($trade->payload, 'context'))->toBe('direct')
        ->and(data_get($trade->payload, 'resources.wood'))->toBe(900)
        ->and(data_get($trade->payload, 'resources.clay'))->toBe(300)
        ->and(data_get($trade->payload, 'merchants'))->toBeGreaterThan(0);

    $balances = $originVillage->fresh()->resource_balances;

    expect($balances['wood'])->toBe(10_000 - 900)
        ->and($balances['clay'])->toBe(10_000 - 300);
});
