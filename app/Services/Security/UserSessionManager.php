<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\User;
use App\Models\UserSession;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Session\Factory as SessionFactory;
use Illuminate\Http\Request;
use Illuminate\Session\Store as SessionStore;
use Illuminate\Support\Collection;

class UserSessionManager
{
    public const SESSION_KEY_STARTED_AT = 'security.session_started_at';

    public const SESSION_KEY_LAST_ACTIVITY = 'security.last_activity_at';

    public const SESSION_KEY_ABSOLUTE_EXPIRES_AT = 'security.absolute_expires_at';

    public function __construct(
        protected SessionFactory $sessionFactory,
    ) {}

    public function register(User $user, Request $request, int $absoluteLifetime): UserSession
    {
        $session = $request->session();
        $sessionId = $session->getId();
        $now = now()->toImmutable();
        $absoluteExpiration = $this->absoluteExpiration($now, $absoluteLifetime);

        $session->put(self::SESSION_KEY_STARTED_AT, $now->getTimestamp());
        $session->put(self::SESSION_KEY_LAST_ACTIVITY, $now->getTimestamp());

        if ($absoluteExpiration === null) {
            $session->forget(self::SESSION_KEY_ABSOLUTE_EXPIRES_AT);
        } else {
            $session->put(self::SESSION_KEY_ABSOLUTE_EXPIRES_AT, $absoluteExpiration->getTimestamp());
        }

        return tap(UserSession::query()->updateOrCreate(
            ['id' => $sessionId],
            [
                'user_id' => $user->getKey(),
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'last_activity_at' => $now,
                'expires_at' => $absoluteExpiration,
            ],
        ));
    }

    public function refresh(User $user, Request $request): void
    {
        $session = $request->session();
        $sessionId = $session->getId();
        $now = now()->toImmutable();

        $session->put(self::SESSION_KEY_LAST_ACTIVITY, $now->getTimestamp());

        $attributes = [
            'user_id' => $user->getKey(),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'last_activity_at' => $now,
        ];

        if ($session->has(self::SESSION_KEY_ABSOLUTE_EXPIRES_AT)) {
            $absoluteTimestamp = (int) $session->get(self::SESSION_KEY_ABSOLUTE_EXPIRES_AT);
            $attributes['expires_at'] = CarbonImmutable::createFromTimestamp($absoluteTimestamp);
        }

        UserSession::query()->updateOrCreate(['id' => $sessionId], $attributes);
    }

    public function invalidate(string $sessionId): void
    {
        $this->sessionStore()->getHandler()->destroy($sessionId);

        UserSession::query()->whereKey($sessionId)->delete();
    }

    public function invalidateAllFor(User $user, ?string $exceptSessionId = null): Collection
    {
        $sessionIds = UserSession::query()
            ->where('user_id', $user->getKey())
            ->when($exceptSessionId, static fn ($query) => $query->where('id', '!=', $exceptSessionId))
            ->pluck('id');

        $handler = $this->sessionStore()->getHandler();

        foreach ($sessionIds as $sessionId) {
            $handler->destroy($sessionId);
        }

        if ($sessionIds->isNotEmpty()) {
            UserSession::query()->whereIn('id', $sessionIds)->delete();
        }

        return $sessionIds;
    }

    public function forget(string $sessionId): void
    {
        UserSession::query()->whereKey($sessionId)->delete();
    }

    protected function sessionStore(): SessionStore
    {
        return $this->sessionFactory->driver();
    }

    protected function absoluteExpiration(CarbonImmutable $now, int $absoluteLifetime): ?CarbonImmutable
    {
        if ($absoluteLifetime <= 0) {
            return null;
        }

        return $now->addMinutes($absoluteLifetime);
    }
}
