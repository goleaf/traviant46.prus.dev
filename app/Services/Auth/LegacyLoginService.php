<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\Activation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LegacyLoginService
{
    public function __construct(private readonly AuthLookupCache $cache) {}

    public function attempt(string $identifier, string $password): ?LegacyLoginResult
    {
        $normalizedIdentifier = $this->normalizeIdentifier($identifier);
        $password = (string) $password;

        if ($normalizedIdentifier === '' || $password === '') {
            return null;
        }

        $user = $this->findActiveUser($normalizedIdentifier);

        if ($user instanceof User) {
            if (Hash::check($password, $user->password)) {
                $this->rehashPasswordIfRequired($user, $password);

                return LegacyLoginResult::owner($user);
            }

            foreach ($this->resolveSitterCandidates($user) as $sitter) {
                if ($sitter instanceof User && Hash::check($password, $sitter->password)) {
                    $this->rehashPasswordIfRequired($sitter, $password);

                    return LegacyLoginResult::sitter($user, $sitter);
                }
            }

            return null;
        }

        $activation = $this->findActivation($normalizedIdentifier);
        if ($activation instanceof Activation && Hash::check($password, $activation->password)) {
            return LegacyLoginResult::activation($activation);
        }

        return null;
    }

    protected function findActiveUser(string $identifier): ?User
    {
        $normalized = $this->normalizeIdentifier($identifier);

        if ($normalized === '') {
            return null;
        }

        return $this->cache->rememberUser($normalized, function () use ($normalized) {
            return User::query()
                ->where(function ($query) use ($normalized) {
                    $query->whereRaw('LOWER(username) = ?', [$normalized])
                        ->orWhereRaw('LOWER(email) = ?', [$normalized]);
                })
                ->first();
        });
    }

    protected function findActivation(string $identifier): ?Activation
    {
        $normalized = $this->normalizeIdentifier($identifier);

        if ($normalized === '') {
            return null;
        }

        return $this->cache->rememberActivation($normalized, function () use ($normalized) {
            return Activation::query()
                ->unused()
                ->where(function ($query) use ($normalized) {
                    $query->whereRaw('LOWER(name) = ?', [$normalized])
                        ->orWhereRaw('LOWER(email) = ?', [$normalized]);
                })
                ->first();
        });
    }

    /**
     * @return Collection<int, User>
     */
    protected function resolveSitterCandidates(User $user): Collection
    {
        return $this->cache->rememberSitterPermissions($user->getKey(), function () use ($user) {
            $legacyIds = collect([$user->sit1_uid, $user->sit2_uid])
                ->filter()
                ->map(static fn ($id) => (int) $id);

            $legacySitters = $legacyIds->isEmpty()
                ? collect()
                : User::query()->whereIn('id', $legacyIds)->get();

            $delegatedSitters = $user->sitters()->get();

            return $legacySitters->merge($delegatedSitters)->unique('id')->values();
        });
    }

    private function rehashPasswordIfRequired(User $user, string $plainPassword): void
    {
        if (! Hash::needsRehash($user->password)) {
            return;
        }

        $user->forceFill([
            'password' => $plainPassword,
        ])->save();
    }

    private function normalizeIdentifier(string $identifier): string
    {
        return Str::lower(trim($identifier));
    }
}
