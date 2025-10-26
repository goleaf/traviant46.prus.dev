<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Auth\LoginSucceeded;
use App\Models\LoginActivity;
use App\Models\User;
use App\Monitoring\Metrics\MetricRecorder;
use App\Services\Security\DeviceFingerprintService;
use App\Services\Security\DeviceVerificationService;
use App\Services\Security\IpAnonymizer;
use App\Services\Security\MultiAccountDetector;
use App\Services\Security\SessionSecurity;
use App\Services\Security\TrustedDeviceManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

class LogSuccessfulLogin
{
    public function __construct(
        protected MultiAccountDetector $detector,
        protected DeviceFingerprintService $fingerprintService,
        protected MetricRecorder $metrics,
        protected SessionSecurity $sessionSecurity,
        protected DeviceVerificationService $deviceVerification,
        protected IpAnonymizer $ipAnonymizer,
        protected TrustedDeviceManager $trustedDevices,
    ) {}

    public function handle(Login $event): void
    {
        $request = request();
        $user = $event->user;
        if (! $user instanceof User) {
            return;
        }

        $actingAsSitter = (bool) $request->session()->get('auth.acting_as_sitter', false);
        $actingSitterId = $actingAsSitter ? $request->session()->get('auth.sitter_id') : null;
        $actingSitterId = is_numeric($actingSitterId) ? (int) $actingSitterId : null;

        $this->sessionSecurity->rotate($request);
        $this->sessionSecurity->storeSnapshot($request, $user);

        $now = now();
        $ipAddress = (string) $request->ip();
        $ipHash = $this->ipAnonymizer->anonymize($ipAddress);
        $userAgent = (string) $request->userAgent();

        $user->forceFill(array_filter([
            'last_login_at' => $now,
            'last_login_ip' => $ipAddress,
            'last_login_ip_hash' => $ipHash,
            'last_owner_login_at' => $actingAsSitter ? null : $now,
        ], static fn ($value) => $value !== null))
            ->save();

        $deviceHash = $this->fingerprintService->hash($request);

        $activity = LoginActivity::create([
            'user_id' => $user->getKey(),
            'acting_sitter_id' => $actingSitterId,
            'ip_address' => $ipAddress,
            'ip_address_hash' => $ipHash,
            'user_agent' => $userAgent,
            'device_hash' => $deviceHash,
            'logged_at' => $now,
            'via_sitter' => $actingAsSitter,
        ]);

        $this->detector->record($activity);
        $this->deviceVerification->notifyIfNewDevice($user, $activity, $actingAsSitter);

        $rememberFlag = $request->boolean('remember_device')
            || (bool) $request->session()->pull('auth.remember_device', false);

        try {
            $this->trustedDevices->resolveCurrentDevice($user, $request);

            if ($rememberFlag) {
                $this->trustedDevices->rememberCurrentDevice($user, $request);
            }
        } catch (AuthorizationException) {
            // Trusted devices disabled; ignore silently.
        }

        $this->metrics->increment('auth.logins', 1.0, [
            'status' => 'success',
            'guard' => (string) $event->guard,
            'via_sitter' => $actingAsSitter ? 'yes' : 'no',
        ]);

        Log::channel('structured')->info('auth.login.succeeded', [
            'user_id' => $user->getKey(),
            'guard' => (string) $event->guard,
            'username' => $user->username,
            'ip_address' => $ipAddress,
            'ip_hash' => $ipHash,
            'device_hash' => $deviceHash,
            'acting_as_sitter' => $actingAsSitter,
            'acting_sitter_id' => $actingSitterId,
            'two_factor_confirmed' => (bool) $user->two_factor_confirmed_at,
            'remember_device' => $rememberFlag,
        ]);

        event(new LoginSucceeded(
            $user,
            (string) $event->guard,
            $ipAddress,
            $userAgent,
            $actingAsSitter,
            $actingSitterId,
        ));
    }
}
