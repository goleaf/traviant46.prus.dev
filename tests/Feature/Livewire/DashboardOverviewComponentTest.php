<?php

declare(strict_types=1);

use App\Livewire\Dashboard\Overview;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\Support\UsesConfiguredDatabase;

uses(UsesConfiguredDatabase::class);

it('exposes beginner protection metrics when active', function (): void {
    $user = User::factory()->create([
        'beginner_protection_until' => Carbon::now()->addHours(5),
    ]);

    $this->actingAs($user);

    Livewire::test(Overview::class)
        ->assertSet('metrics.beginnerProtection.active', true)
        ->assertSet('metrics.beginnerProtection.remaining', static fn ($value) => is_string($value) && $value !== '')
        ->assertSet('metrics.beginnerProtection.endsAtLabel', static fn ($value) => is_string($value) && $value !== '')
        ->assertSee(__('Beginner protection active'));
});

it('hides beginner protection notice when inactive', function (): void {
    $user = User::factory()->create([
        'beginner_protection_until' => Carbon::now()->subMinute(),
    ]);

    $this->actingAs($user);

    Livewire::test(Overview::class)
        ->assertSet('metrics.beginnerProtection.active', false)
        ->assertDontSee(__('Beginner protection active'));
});
