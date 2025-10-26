<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\LoginActivity;
use App\Models\User;
use App\Services\Security\DeviceFingerprintService;
use App\Services\Security\MultiAccountDetector;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function __construct(
        protected MultiAccountDetector $detector,
        protected DeviceFingerprintService $fingerprintService,
    ) {}

    public function handle(Login $event): void
    {
        $request = request();
        $user = $event->user;
        if (! $user instanceof User) {
            return;
        }

        $actingAsSitter = (bool) $request->session()->get('auth.acting_as_sitter', false);
        $actingSitterId = $request->session()->get('auth.sitter_id');

        $now = now();
        $user->forceFill(array_filter([
            'last_login_at' => $now,
            'last_login_ip' => $request->ip(),
            'last_owner_login_at' => $actingAsSitter ? null : $now,
        ], static fn ($value) => $value !== null))
            ->save();

        $deviceHash = $this->fingerprintService->hash($request);

        $activity = LoginActivity::create([
            'user_id' => $user->getKey(),
            'acting_sitter_id' => $actingAsSitter ? $actingSitterId : null,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'device_hash' => $deviceHash,
            'logged_at' => $now,
            'via_sitter' => $actingAsSitter,
        ]);

        $this->detector->record($activity);
    }
}
