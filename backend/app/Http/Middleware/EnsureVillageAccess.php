<?php

namespace App\Http\Middleware;

use App\Models\Village;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EnsureVillageAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Authenticatable|null $user */
        $user = $request->user();

        $village = $request->route('village');

        if ($village === null) {
            return $next($request);
        }

        if (! $user instanceof Authenticatable) {
            abort(403);
        }

        if (! $village instanceof Village) {
            $village = Village::query()->findOrFail($village);
            $request->route()->setParameter('village', $village);
        }

        Gate::forUser($user)->authorize('view', $village);

        return $next($request);
    }
}
