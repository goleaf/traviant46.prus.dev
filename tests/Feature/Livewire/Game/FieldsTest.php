<?php

declare(strict_types=1);

use App\Models\Game\BuildQueue;
use App\Models\Game\ResourceField;
use App\Models\Game\Village;
use App\Models\User;
use Livewire\Livewire;
use Tests\Support\UsesConfiguredDatabase;

uses(UsesConfiguredDatabase::class);

beforeEach(function (): void {
    Livewire::component('game.fields', \App\Livewire\Game\Fields::class);
    Livewire::component('game::fields', \App\Livewire\Game\Fields::class);
});

it('renders resource fields for the village', function (): void {
    $user = User::factory()->create();
    $village = Village::factory()->starter()->for($user, 'owner')->create();

    $this->actingAs($user);

    Livewire::test(\App\Livewire\Game\Fields::class, ['village' => $village])
        ->assertStatus(200)
        ->assertSee('Resource fields')
        ->assertSee('Woodcutter')
        ->assertSee('Upgrade');
});

it('shows capital lock reasons when attempting to exceed non-capital limits', function (): void {
    $user = User::factory()->create();
    $village = Village::factory()
        ->starter()
        ->for($user, 'owner')
        ->create(['is_capital' => false]);

    /** @var ResourceField $field */
    $field = $village->resourceFields()->where('kind', 'wood')->firstOrFail();
    $field->update(['level' => 10]);

    $this->actingAs($user);

    Livewire::test(\App\Livewire\Game\Fields::class, ['village' => $village->fresh()])
        ->assertSee('Only capital villages can upgrade Woodcutter beyond level 10.');
});

it('enqueues a resource field upgrade through the livewire action', function (): void {
    $user = User::factory()->create();
    $village = Village::factory()->starter()->for($user, 'owner')->create();

    $fieldId = $village->resourceFields()->where('kind', 'wood')->firstOrFail()->getKey();

    $this->actingAs($user);

    Livewire::test(\App\Livewire\Game\Fields::class, ['village' => $village])
        ->call('enqueueUpgrade', $fieldId)
        ->assertSet('noticeType', 'success')
        ->assertSet('notice', static fn ($value): bool => is_string($value) && str_contains($value, 'Woodcutter'));

    expect(BuildQueue::query()->count())->toBe(1);
});
