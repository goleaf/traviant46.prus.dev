<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\post;

uses(RefreshDatabase::class);

it('skips reserved legacy uids when registering new players', function (): void {
    User::factory()->create(['legacy_uid' => User::LEGACY_ADMIN_UID]);
    User::factory()->create(['legacy_uid' => 1]);

    $existingMax = (int) User::query()->max('legacy_uid');

    post('/register', [
        'username' => 'newplayer',
        'name' => 'New Player',
        'email' => 'player@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ])->assertRedirect('/home');

    $user = User::query()->where('email', 'player@example.com')->firstOrFail();

    $expectedLegacyUid = $existingMax + 1;

    while (User::isReservedLegacyUid($expectedLegacyUid)) {
        $expectedLegacyUid++;
    }

    expect($user->legacy_uid)->toBe($expectedLegacyUid)
        ->and(User::reservedLegacyUids())->not->toContain((int) $user->legacy_uid);
});
