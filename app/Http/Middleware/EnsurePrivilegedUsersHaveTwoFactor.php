<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\StaffRole;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePrivilegedUsersHaveTwoFactor
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if (! $this->requiresTwoFactor($user)) {
            return $next($request);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return $next($request);
        }

        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(423, __('Two-factor authentication is required to access this resource.'));
        }

        return redirect()
            ->route('security.two-factor')
            ->with('status', 'two-factor-required');
    }

    private function shouldBypass(Request $request): bool
    {
        if (! $request->route()) {
            return false;
        }

        if ($request->routeIs([
            'logout',
            'security.two-factor',
            'two-factor.*',
            'password.confirm',
            'password.confirmation',
        ])) {
            return true;
        }

        return false;
    }

    private function requiresTwoFactor(User $user): bool
    {
        if ($user->isAdmin() || $user->isMultihunter()) {
            return true;
        }

        return $user->staffRole() !== StaffRole::Player;
    }
}
