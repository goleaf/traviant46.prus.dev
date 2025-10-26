<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\SitterDelegation;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSitterSessionIsValid
{
    /**
     * @param Closure(Request): (Response|RedirectResponse) $next
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = $request->user();
        $session = $request->session();

        if (! $user || ! $session->get('auth.acting_as_sitter', false)) {
            return $next($request);
        }

        $sitterId = (int) $session->get('auth.sitter_id');

        if ($sitterId <= 0) {
            return $this->invalidateSitterSession($request);
        }

        $delegationExists = SitterDelegation::query()
            ->forAccount($user)
            ->forSitter($sitterId)
            ->active()
            ->exists();

        if ($delegationExists) {
            return $next($request);
        }

        return $this->invalidateSitterSession($request, __('Your sitter permissions were revoked by the account owner.'));
    }

    private function invalidateSitterSession(Request $request, ?string $error = null): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = $error ?? __('Your sitter session is no longer valid. Please sign in again.');

        return redirect()
            ->route('login')
            ->withErrors(['login' => $message]);
    }
}
