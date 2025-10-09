<?php

use App\Models\Activation;
use App\Models\User;
use App\Services\Auth\LegacyLoginResult;
use Illuminate\Support\Facades\Hash;

return [
    'owner via username' => function () {
        $password = 'Owner#1234';
        $user = User::factory()->create([
            'username' => 'playerone',
            'email' => 'player@example.com',
            'password' => Hash::make($password),
        ]);

        return [
            'identifier' => 'playerone',
            'password' => $password,
            'expected' => [
                'mode' => LegacyLoginResult::MODE_OWNER,
                'user_id' => $user->getKey(),
                'sitter_id' => null,
                'activation_id' => null,
            ],
        ];
    },
    'owner via email' => function () {
        $password = 'Email#1234';
        $user = User::factory()->create([
            'username' => 'player',
            'email' => 'email@example.com',
            'password' => Hash::make($password),
        ]);

        return [
            'identifier' => 'email@example.com',
            'password' => $password,
            'expected' => [
                'mode' => LegacyLoginResult::MODE_OWNER,
                'user_id' => $user->getKey(),
                'sitter_id' => null,
                'activation_id' => null,
            ],
        ];
    },
    'sitter via legacy slot one' => function () {
        $owner = User::factory()->create([
            'username' => 'owner',
            'email' => 'owner@example.com',
        ]);

        $password = 'Sitter#4321';
        $sitter = User::factory()->create([
            'username' => 'sitter-one',
            'email' => 'sitter@example.com',
            'password' => Hash::make($password),
        ]);

        $owner->forceFill(['sit1_uid' => $sitter->getKey()])->save();

        return [
            'identifier' => 'owner',
            'password' => $password,
            'expected' => [
                'mode' => LegacyLoginResult::MODE_SITTER,
                'user_id' => $owner->getKey(),
                'sitter_id' => $sitter->getKey(),
                'activation_id' => null,
            ],
        ];
    },
    'sitter via legacy slot two' => function () {
        $owner = User::factory()->create([
            'username' => 'owner-two',
            'email' => 'owner-two@example.com',
        ]);

        $password = 'Second#999';
        $sitter = User::factory()->create([
            'username' => 'sitter-two',
            'email' => 'sitter-two@example.com',
            'password' => Hash::make($password),
        ]);

        $owner->forceFill(['sit2_uid' => $sitter->getKey()])->save();

        return [
            'identifier' => 'owner-two',
            'password' => $password,
            'expected' => [
                'mode' => LegacyLoginResult::MODE_SITTER,
                'user_id' => $owner->getKey(),
                'sitter_id' => $sitter->getKey(),
                'activation_id' => null,
            ],
        ];
    },
    'activation pending' => function () {
        $password = 'Activate#111';
        $activation = Activation::query()->create([
            'name' => 'pending',
            'email' => 'pending@example.com',
            'token' => 'ACT-123',
            'password' => Hash::make($password),
            'world_id' => 's1',
            'used' => false,
        ]);

        return [
            'identifier' => 'pending',
            'password' => $password,
            'expected' => [
                'mode' => LegacyLoginResult::MODE_ACTIVATION,
                'user_id' => null,
                'sitter_id' => null,
                'activation_id' => $activation->getKey(),
            ],
        ];
    },
    'invalid credentials' => function () {
        User::factory()->create([
            'username' => 'someone',
            'email' => 'someone@example.com',
            'password' => Hash::make('Valid#123'),
        ]);

        return [
            'identifier' => 'someone',
            'password' => 'Wrong#321',
            'expected' => [
                'mode' => null,
                'user_id' => null,
                'sitter_id' => null,
                'activation_id' => null,
            ],
        ];
    },
];
