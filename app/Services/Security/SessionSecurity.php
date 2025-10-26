<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\User;
use Illuminate\Http\Request;

class SessionSecurity
{
    public function rotate(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $request->session()->regenerate(true);
    }

    public function storeSnapshot(Request $request, User $user): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $request->session()->put('security.privilege_hash', $this->fingerprint($request, $user));
    }

    public function snapshotIsFresh(Request $request, User $user): bool
    {
        if (! $request->hasSession()) {
            return true;
        }

        $current = $request->session()->get('security.privilege_hash');

        if (! is_string($current) || $current === '') {
            $this->storeSnapshot($request, $user);

            return true;
        }

        return hash_equals($current, $this->fingerprint($request, $user));
    }

    private function fingerprint(Request $request, User $user): string
    {
        $actingAsSitter = (bool) $request->session()->get('auth.acting_as_sitter', false);
        $sitterId = $request->session()->get('auth.sitter_id');

        $components = [
            (string) $user->getKey(),
            $user->staffRole()->value,
            $user->isAdmin() ? '1' : '0',
            $user->isMultihunter() ? '1' : '0',
            $actingAsSitter ? '1' : '0',
            $actingAsSitter ? (string) $sitterId : '0',
        ];

        return hash('sha256', implode('|', $components));
    }
}
