<?php

declare(strict_types=1);

use App\Enums\SitterPermissionPreset;
use App\Models\SitterDelegation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('kicks active sitter sessions after delegation revocation', function (): void {
    $ownerPassword = 'Owner#3141';
    $sitterPassword = 'Sitter#2718';

    $owner = User::factory()->create([
        'username' => 'owner-alpha',
        'email' => 'owner-alpha@example.com',
        'password' => Hash::make($ownerPassword),
    ]);

    $sitter = User::factory()->create([
        'username' => 'sitter-beta',
        'email' => 'sitter-beta@example.com',
        'password' => Hash::make($sitterPassword),
    ]);

    SitterDelegation::query()->create([
        'owner_user_id' => $owner->getKey(),
        'sitter_user_id' => $sitter->getKey(),
        'permissions' => SitterPermissionPreset::Guardian->permissionKeys(),
        'created_by' => $owner->getKey(),
        'updated_by' => $owner->getKey(),
    ]);

    $this->post('/login', [
        'login' => $owner->username,
        'password' => $sitterPassword,
    ])->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($owner);
    expect(session()->get('auth.acting_as_sitter'))->toBeTrue();

    SitterDelegation::query()
        ->forAccount($owner)
        ->forSitter($sitter)
        ->delete();

    $response = $this->get('/home');

    $response->assertRedirect(route('login'))
        ->assertSessionHasErrors('login');

    $this->assertGuest();
});
