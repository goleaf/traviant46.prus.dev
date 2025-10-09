<?php

namespace App\Listeners;

use App\Models\LoginActivity;
use App\Models\User;
use App\Services\Security\MultiAccountDetector;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function __construct(
        protected MultiAccountDetector $detector,
    ) {}

    public function handle(Login $event): void
    {
        $request = request();
        $user = $event->user;
        if (! $user instanceof User) {
            return;
        }

        $actingAsSitter = (bool) $request->session()->pull('auth.acting_as_sitter', false);
        $actingSitterId = $request->session()->pull('auth.sitter_id');

        $now = now();
        $user->forceFill(array_filter([
            'last_login_at' => $now,
            'last_login_ip' => $request->ip(),
            'last_owner_login_at' => $actingAsSitter ? null : $now,
        ], static fn ($value) => $value !== null))
            ->save();

        $activity = LoginActivity::create([
            'user_id' => $user->getKey(),
            'acting_sitter_id' => $actingAsSitter ? $actingSitterId : null,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'via_sitter' => $actingAsSitter,
        ]);

        $this->detector->record(
            $user,
            $activity->ip_address,
            $activity->created_at,
            $actingAsSitter,
            $activity->acting_sitter_id
        );
    }
}
