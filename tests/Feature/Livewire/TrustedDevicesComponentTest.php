<?php

declare(strict_types=1);

use App\Livewire\Account\TrustedDevices as TrustedDevicesComponent;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    Config::set('security.trusted_devices.enabled', true);
    Config::set('security.trusted_devices.cookie.name', 'travian_trusted_device');
    Config::set('security.trusted_devices.cookie.lifetime_days', 180);
    Config::set('security.trusted_devices.default_expiration_days', 180);
});

it('allows a user to trust the current device via Livewire action', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(TrustedDevicesComponent::class)
        ->set('rememberLabel', 'Studio rig')
        ->call('rememberDevice')
        ->assertSet('statusMessage', __('This device is now trusted. Future high-risk actions will skip additional prompts.'));

    $device = TrustedDevice::query()->forUser($user)->first();

    expect($device)->not->toBeNull();
    expect($device->label)->toEqual('Studio rig');
});

it('renames and revokes trusted devices through the component', function (): void {
    $user = User::factory()->create();
    $device = TrustedDevice::factory()->for($user)->create([
        'label' => 'Old label',
        'revoked_at' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(TrustedDevicesComponent::class)
        ->set("renameLabels.{$device->public_id}", 'Battle station')
        ->call('renameDevice', $device->public_id)
        ->assertSet('statusMessage', __('Device label updated.'))
        ->call('revokeDevice', $device->public_id)
        ->assertSet('statusMessage', __('The selected device has been revoked and must re-verify before acting.'));

    $device->refresh();

    expect($device->label)->toEqual('Battle station');
    expect($device->revoked_at)->not->toBeNull();
});
