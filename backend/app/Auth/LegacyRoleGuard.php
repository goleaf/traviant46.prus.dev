<?php

namespace App\Auth;

use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Timebox;
use Symfony\Component\HttpFoundation\Request;

class LegacyRoleGuard extends SessionGuard
{
    protected ?int $requiredLegacyUid;

    public function __construct(
        string $name,
        UserProvider $provider,
        Session $session,
        ?Request $request = null,
        ?Timebox $timebox = null,
        bool $rehashOnLogin = true,
        int $timeboxDuration = 200000,
        ?int $requiredLegacyUid = null,
    ) {
        parent::__construct($name, $provider, $session, $request, $timebox, $rehashOnLogin, $timeboxDuration);

        $this->requiredLegacyUid = $requiredLegacyUid;
    }

    protected function hasValidCredentials(AuthenticatableContract $user, array $credentials): bool
    {
        if ($this->requiredLegacyUid !== null && (int) ($user->legacy_uid ?? -1) !== $this->requiredLegacyUid) {
            return false;
        }

        return parent::hasValidCredentials($user, $credentials);
    }

    public function user(): ?AuthenticatableContract
    {
        $user = parent::user();

        if ($user && $this->requiredLegacyUid !== null && (int) ($user->legacy_uid ?? -1) !== $this->requiredLegacyUid) {
            $this->logout();

            return null;
        }

        return $user;
    }
}
