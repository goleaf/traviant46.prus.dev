<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Security\UserSessionManager;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceSessionTtl
{
    public function __construct(
        protected UserSessionManager $sessionManager,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession() || $request->user() === null) {
            return $next($request);
        }

        $session = $request->session();
        $now = CarbonImmutable::now();
        $idleMinutes = (int) config('session.lifetime', 0);
        $absoluteMinutes = (int) config('session.absolute_lifetime', 0);

        $lastActivityTimestamp = $session->get(UserSessionManager::SESSION_KEY_LAST_ACTIVITY);
        $absoluteExpiresTimestamp = $session->get(UserSessionManager::SESSION_KEY_ABSOLUTE_EXPIRES_AT);
        $startedTimestamp = $session->get(UserSessionManager::SESSION_KEY_STARTED_AT);

        if ($startedTimestamp === null) {
            $this->sessionManager->register($request->user(), $request, $absoluteMinutes);
            $absoluteExpiresTimestamp = $session->get(UserSessionManager::SESSION_KEY_ABSOLUTE_EXPIRES_AT);
        }

        if ($absoluteExpiresTimestamp === null && $absoluteMinutes > 0 && $startedTimestamp !== null) {
            $absoluteDeadline = CarbonImmutable::createFromTimestamp($startedTimestamp)->addMinutes($absoluteMinutes);
            $session->put(UserSessionManager::SESSION_KEY_ABSOLUTE_EXPIRES_AT, $absoluteDeadline->getTimestamp());
            $absoluteExpiresTimestamp = $absoluteDeadline->getTimestamp();
        }

        if ($absoluteExpiresTimestamp !== null) {
            $absoluteDeadline = CarbonImmutable::createFromTimestamp((int) $absoluteExpiresTimestamp);

            if ($absoluteDeadline->lessThanOrEqualTo($now)) {
                return $this->terminateSession(
                    $request,
                    __('Your session has reached its maximum duration. Please sign in again.'),
                );
            }
        }

        if ($lastActivityTimestamp !== null && $idleMinutes > 0) {
            $lastActivity = CarbonImmutable::createFromTimestamp((int) $lastActivityTimestamp);

            if ($lastActivity->addMinutes($idleMinutes)->lessThanOrEqualTo($now)) {
                return $this->terminateSession(
                    $request,
                    __('You were signed out due to inactivity. Please sign in again.'),
                );
            }
        }

        $this->sessionManager->refresh($request->user(), $request);

        return $next($request);
    }

    protected function terminateSession(Request $request, string $message): RedirectResponse
    {
        $sessionId = $request->session()->getId();

        $this->sessionManager->invalidate($sessionId);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withErrors(['session' => $message]);
    }
}
