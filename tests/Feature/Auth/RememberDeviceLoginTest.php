<?php

declare(strict_types=1);

use App\Listeners\LogSuccessfulLogin;
use App\Models\User;
use App\Services\Security\DeviceVerificationService;
use Illuminate\Auth\Events\Login as LoginEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\mock;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('stores a trusted device when the login form requests it', function (): void {
    Config::set('security.trusted_devices.enabled', true);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'password' => Hash::make('secret-pass'),
    ]);

    $request = Request::create('/login', 'POST', [
        'remember_device' => '1',
    ], [], [], [
        'REMOTE_ADDR' => '203.0.113.55',
        'HTTP_USER_AGENT' => 'Feature Test Browser/2.0',
    ]);

    $session = app('session')->driver();
    $session->start();
    $request->setLaravelSession($session);
    $request->setUserResolver(fn () => $user);
    app()->instance('request', $request);

    mock(DeviceVerificationService::class)
        ->shouldReceive('notifyIfNewDevice')
        ->andReturnNull();

    /** @var LogSuccessfulLogin $listener */
    $listener = app(LogSuccessfulLogin::class);

    $listener->handle(new LoginEvent('web', $user, false));

    expect($user->fresh()->trustedDevices()->count())->toBe(1);
});
