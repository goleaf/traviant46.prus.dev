<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;

class LegacyRoleUserProvider extends EloquentUserProvider
{
    public function __construct(HasherContract $hasher, string $model, private readonly int $legacyUid)
    {
        parent::__construct($hasher, $model);
    }

    public function retrieveById($identifier): ?AuthenticatableContract
    {
        $user = parent::retrieveById($identifier);

        return $this->filterUser($user);
    }

    public function retrieveByToken($identifier, $token): ?AuthenticatableContract
    {
        $user = parent::retrieveByToken($identifier, $token);

        return $this->filterUser($user);
    }

    public function retrieveByCredentials(array $credentials): ?AuthenticatableContract
    {
        $user = parent::retrieveByCredentials($credentials);

        return $this->filterUser($user);
    }

    private function filterUser(?AuthenticatableContract $user): ?AuthenticatableContract
    {
        if ($user === null) {
            return null;
        }

        $legacyUid = $user->legacy_uid ?? null;

        if ($legacyUid === null) {
            return null;
        }

        return (int) $legacyUid === $this->legacyUid ? $user : null;
    }
}
