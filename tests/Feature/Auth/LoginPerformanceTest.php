<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Auth\LegacyLoginService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('authenticates repeated owner logins within acceptable throughput', function (): void {
    $password = 'Throughput#123';
    $user = User::factory()->create([
        'username' => 'throughput-user',
        'password' => Hash::make($password),
    ]);

    $service = app(LegacyLoginService::class);

    $start = microtime(true);

    foreach (range(1, 50) as $iteration) {
        $result = $service->attempt($user->username, $password);
        expect($result)->not->toBeNull();
    }

    $duration = microtime(true) - $start;

    expect($duration)->toBeLessThan(2.0);
});
