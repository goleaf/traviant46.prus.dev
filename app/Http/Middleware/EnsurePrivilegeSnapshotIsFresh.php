<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Security\SessionSecurity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePrivilegeSnapshotIsFresh
{
    public function __construct(private readonly SessionSecurity $sessionSecurity) {}

    /**
     * Handle an incoming request.
     *
     * @param Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($request->hasSession() && $user instanceof User) {
            if (! $this->sessionSecurity->snapshotIsFresh($request, $user)) {
                $this->sessionSecurity->rotate($request);
                $this->sessionSecurity->storeSnapshot($request, $user);
            }
        }

        return $next($request);
    }
}
