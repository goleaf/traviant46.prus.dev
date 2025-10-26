<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirects banned legacy accounts to the banned notice, mirroring the legacy GameCtrl guard.
 */
class EnsureAccountNotBanned
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response|RedirectResponse) $next
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::guard($this->resolveGuard())->user();

        if (! $user) {
            return $next($request);
        }

        if ($request->routeIs('game.banned')) {
            return $next($request);
        }

        if (! $user->isBanned()) {
            return $next($request);
        }

        return redirect()->route('game.banned');
    }

    private function resolveGuard(): string
    {
        $guard = (string) config('fortify.guard', '');

        if ($guard === '') {
            $guard = (string) config('auth.defaults.guard', 'web');
        }

        return $guard !== '' ? $guard : 'web';
    }
}
