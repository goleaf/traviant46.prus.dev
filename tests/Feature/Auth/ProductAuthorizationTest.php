<?php

declare(strict_types=1);

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('denies product catalogue access to players', function (): void {
    $user = User::factory()->create([
        'role' => StaffRole::Player,
    ]);

    $this->actingAs($user);

    $this->get(route('frontend.orders.create'))->assertForbidden();
});

it('allows product managers to load order creation', function (): void {
    $user = User::factory()->create([
        'role' => StaffRole::ProductManager,
    ]);

    $this->actingAs($user);

    $this->get(route('frontend.orders.create'))->assertOk();
});

it('allows administrators to load order creation', function (): void {
    $user = User::factory()->create([
        'role' => StaffRole::Admin,
    ]);

    $this->actingAs($user);

    $this->get(route('frontend.orders.create'))->assertOk();
});
