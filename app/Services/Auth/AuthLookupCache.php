<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\Activation;
use App\Models\User;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AuthLookupCache
{
    public function __construct(
        private readonly CacheFactory $cacheFactory,
        private readonly ConfigRepository $config
    ) {}

    public function rememberUser(string $identifier, callable $resolver): ?User
    {
        $key = $this->userKey($identifier);

        return $this->store()->remember($key, $this->ttl('security.cache.auth_lookup_ttl'), function () use ($resolver) {
            $user = $resolver();

            return $user instanceof User ? $user->fresh() ?? $user : null;
        });
    }

    public function forgetUser(User $user): void
    {
        $identifiers = array_filter([
            $user->username,
            $user->email,
            $user->getOriginal('username'),
            $user->getOriginal('email'),
        ], static fn ($value) => is_string($value) && $value !== '');

        $store = $this->store();

        foreach ($identifiers as $identifier) {
            $store->forget($this->userKey((string) $identifier));
        }
    }

    public function rememberActivation(string $identifier, callable $resolver): ?Activation
    {
        $key = $this->activationKey($identifier);

        return $this->store()->remember($key, $this->ttl('security.cache.activation_lookup_ttl'), function () use ($resolver) {
            $activation = $resolver();

            return $activation instanceof Activation ? $activation->fresh() ?? $activation : null;
        });
    }

    public function forgetActivation(Activation $activation): void
    {
        $identifiers = array_filter([
            $activation->name,
            $activation->email,
        ], static fn ($value) => is_string($value) && $value !== '');

        $store = $this->store();

        foreach ($identifiers as $identifier) {
            $store->forget($this->activationKey((string) $identifier));
        }
    }

    /**
     * @return Collection<int, User>
     */
    public function rememberSitterPermissions(int $accountId, callable $resolver): Collection
    {
        $key = $this->permissionKey($accountId);

        return $this->store()->remember($key, $this->ttl('security.cache.permission_set_ttl'), function () use ($resolver) {
            $result = $resolver();

            if ($result instanceof Collection) {
                return $result;
            }

            return collect($result);
        });
    }

    public function forgetSitterPermissions(int $accountId): void
    {
        $this->store()->forget($this->permissionKey($accountId));
    }

    private function store(): CacheRepository
    {
        $store = $this->config->get('security.cache.store');

        return $store ? $this->cacheFactory->store((string) $store) : $this->cacheFactory->store();
    }

    private function ttl(string $configKey): int
    {
        return max(1, (int) $this->config->get($configKey, 300));
    }

    private function userKey(string $identifier): string
    {
        return 'auth:lookup:user:'.sha1(Str::lower(trim($identifier)));
    }

    private function activationKey(string $identifier): string
    {
        return 'auth:lookup:activation:'.sha1(Str::lower(trim($identifier)));
    }

    private function permissionKey(int $accountId): string
    {
        return 'auth:lookup:sitter-permissions:'.sha1((string) $accountId);
    }
}
