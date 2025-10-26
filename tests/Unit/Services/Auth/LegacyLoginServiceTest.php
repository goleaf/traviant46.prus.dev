<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Auth\LegacyLoginResult;
use App\Services\Auth\LegacyLoginService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

dataset('legacy-login-identifiers', [
    'username' => static fn (User $user): string => $user->username,
    'email' => static fn (User $user): string => $user->email,
    'trimmed username' => static fn (User $user): string => '  '.$user->username.'  ',
    'trimmed email' => static fn (User $user): string => '  '.$user->email.'  ',
    'uppercase email' => static fn (User $user): string => strtoupper($user->email),
    'uppercase username' => static fn (User $user): string => strtoupper($user->username),
]);

it('authenticates the account owner via supported identifiers', function (callable $identifierResolver): void {
    $password = 'Secret#90210';

    $user = User::factory()->create([
        'username' => 'owner-account',
        'email' => 'owner@example.com',
        'password' => Hash::make($password),
    ]);

    $service = app(LegacyLoginService::class);

    $result = $service->attempt($identifierResolver($user), $password);

    expect($result)
        ->toBeInstanceOf(LegacyLoginResult::class)
        ->and($result->mode)->toBe(LegacyLoginResult::MODE_OWNER)
        ->and($result->successful())->toBeTrue()
        ->and(optional($result->user)->is($user))->toBeTrue()
        ->and($result->sitter)->toBeNull();
})->with('legacy-login-identifiers');

it('returns null when the supplied password does not match', function (): void {
    $user = User::factory()->create([
        'username' => 'wrong-pass',
        'password' => Hash::make('Correct#Password1'),
    ]);

    $service = app(LegacyLoginService::class);

    $result = $service->attempt($user->username, 'TotallyWrong');

    expect($result)->toBeNull();
});
