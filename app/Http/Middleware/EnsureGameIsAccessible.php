<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies maintenance and start-time gates before exposing Livewire gameplay routes.
 */
class EnsureGameIsAccessible
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

        if ($this->shouldBypassChecks($user)) {
            return $next($request);
        }

        $startTime = Config::get('game.start_time');

        if ($this->gameHasNotStarted($startTime)) {
            return redirect()->route('landing');
        }

        if ($this->isInMaintenance() && ! $request->routeIs(['game.maintenance', 'game.banned'])) {
            return redirect()->route('game.maintenance');
        }

        return $next($request);
    }

    private function shouldBypassChecks(User $user): bool
    {
        /** @var array<int, int|string> $allowedLegacyIds */
        $allowedLegacyIds = Config::get('game.maintenance.allowed_legacy_uids', []);

        return in_array((int) ($user->legacy_uid ?? -1), $allowedLegacyIds, true);
    }

    private function gameHasNotStarted(?string $startTime): bool
    {
        if ($startTime === null || trim($startTime) === '') {
            return false;
        }

        $start = Carbon::parse($startTime);

        return Date::now()->lt($start);
    }

    private function isInMaintenance(): bool
    {
        return (bool) Config::get('game.maintenance.enabled', false);
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
