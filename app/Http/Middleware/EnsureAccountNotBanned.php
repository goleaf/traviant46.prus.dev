<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountNotBanned
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

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
}
