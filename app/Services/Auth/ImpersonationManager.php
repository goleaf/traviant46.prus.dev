<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\Impersonation;
use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ImpersonationManager
{
    public function start(User $impersonator, User $target, array $context = []): Impersonation
    {
        if ($impersonator->is($target)) {
            throw new InvalidArgumentException('Cannot impersonate your own account.');
        }

        if ($target->isAdmin()) {
            throw new InvalidArgumentException('Cannot impersonate the legacy administrator account.');
        }

        if ($target->isMultihunter()) {
            throw new InvalidArgumentException('Cannot impersonate the multihunter sentinel.');
        }

        $this->ensureNoActiveImpersonation($impersonator);

        $now = Carbon::now();
        $request = request();
        $session = $request->session();

        $impersonation = Impersonation::query()->create([
            'impersonator_id' => $impersonator->getKey(),
            'impersonated_user_id' => $target->getKey(),
            'started_at' => $now,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'context' => $context,
        ]);

        $session->put('impersonation', [
            'id' => $impersonation->getKey(),
            'impersonator_id' => $impersonator->getKey(),
            'impersonator_name' => $impersonator->username,
            'impersonated_user_id' => $target->getKey(),
            'impersonated_name' => $target->username,
        ]);

        $session->put('impersonation.active', true);
        $session->put('impersonation.admin_user_id', $impersonator->getKey());

        $this->guard()->login($target);
        Auth::guard('admin')->login($impersonator);

        Log::channel('stack')->info('Admin impersonation started', [
            'impersonation_id' => $impersonation->getKey(),
            'impersonator_id' => $impersonator->getKey(),
            'target_id' => $target->getKey(),
            'context' => $context,
        ]);

        return $impersonation;
    }

    public function stop(?string $reason = null): ?User
    {
        $session = request()->session();
        $payload = $session->get('impersonation');

        if (! is_array($payload) || ! isset($payload['id'])) {
            return null;
        }

        $impersonation = Impersonation::query()->find($payload['id']);

        if ($impersonation !== null && $impersonation->ended_at === null) {
            $impersonation->forceFill([
                'ended_at' => Carbon::now(),
                'ended_reason' => $reason,
            ])->save();
        }

        $impersonator = null;

        if (isset($payload['impersonator_id'])) {
            $impersonator = User::query()->find($payload['impersonator_id']);
        }

        $session->forget([
            'impersonation',
            'impersonation.active',
            'impersonation.admin_user_id',
        ]);

        $this->guard()->logout();

        if ($impersonator instanceof User) {
            $this->guard()->login($impersonator);
            Auth::guard('admin')->login($impersonator);

            Log::channel('stack')->info('Admin impersonation ended', [
                'impersonation_id' => $impersonation?->getKey(),
                'impersonator_id' => $impersonator->getKey(),
                'reason' => $reason,
            ]);
        }

        return $impersonator;
    }

    private function ensureNoActiveImpersonation(User $impersonator): void
    {
        $session = request()->session();
        $payload = $session->get('impersonation');

        if (! is_array($payload) || ! isset($payload['id'])) {
            return;
        }

        if ((int) ($payload['impersonator_id'] ?? 0) !== $impersonator->getKey()) {
            return;
        }

        $this->stop('replaced');
    }

    private function guard(): StatefulGuard
    {
        $guard = Auth::guard('web');

        if (! $guard instanceof StatefulGuard) {
            throw new InvalidArgumentException(Str::of($guard::class)->append(' is not stateful.')->toString());
        }

        return $guard;
    }
}
