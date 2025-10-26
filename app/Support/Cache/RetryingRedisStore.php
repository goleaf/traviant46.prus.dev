<?php

declare(strict_types=1);

namespace App\Support\Cache;

use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Facades\Log;
use RedisClusterException;
use RedisException;
use RuntimeException;
use Throwable;

/**
 * @implements \Illuminate\Contracts\Cache\Store
 */
class RetryingRedisStore implements LockProvider, Store
{
    /**
     * @var callable(int): void
     */
    private $sleep;

    public function __construct(
        private readonly RedisStore $store,
        private readonly int $maxAttempts = 3,
        private readonly int $baseDelayMilliseconds = 50,
        private readonly int $maxDelayMilliseconds = 1000,
        ?callable $sleep = null,
    ) {
        if ($this->maxAttempts < 1) {
            throw new RuntimeException('RetryingRedisStore requires at least one attempt.');
        }

        $this->sleep = $sleep ?? static function (int $milliseconds): void {
            usleep(max(0, $milliseconds) * 1000);
        };
    }

    public function get($key): mixed
    {
        return $this->withRetry('get', fn () => $this->store->get($key));
    }

    public function many(array $keys): array
    {
        return $this->withRetry('many', fn () => $this->store->many($keys));
    }

    public function put($key, $value, $seconds): bool
    {
        return $this->withRetry('put', fn () => $this->store->put($key, $value, $seconds));
    }

    public function putMany(array $values, $seconds): bool
    {
        return $this->withRetry('putMany', fn () => $this->store->putMany($values, $seconds));
    }

    public function increment($key, $value = 1): int|bool
    {
        return $this->withRetry('increment', fn () => $this->store->increment($key, $value));
    }

    public function decrement($key, $value = 1): int|bool
    {
        return $this->withRetry('decrement', fn () => $this->store->decrement($key, $value));
    }

    public function forever($key, $value): bool
    {
        return $this->withRetry('forever', fn () => $this->store->forever($key, $value));
    }

    public function forget($key): bool
    {
        return $this->withRetry('forget', fn () => $this->store->forget($key));
    }

    public function flush(): bool
    {
        return $this->withRetry('flush', fn () => $this->store->flush());
    }

    public function getPrefix(): string
    {
        return $this->store->getPrefix();
    }

    public function add($key, $value, $seconds)
    {
        return $this->withRetry('add', fn () => $this->store->add($key, $value, $seconds));
    }

    public function lock($name, $seconds = 0, $owner = null)
    {
        return $this->store->lock($name, $seconds, $owner);
    }

    public function restoreLock($name, $owner)
    {
        return $this->store->restoreLock($name, $owner);
    }

    public function setLockConnection($connection)
    {
        $this->store->setLockConnection($connection);

        return $this;
    }

    public function lockConnection()
    {
        return $this->store->lockConnection();
    }

    public function getRedis()
    {
        return $this->store->getRedis();
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->store->{$name}(...$arguments);
    }

    /**
     * @template TReturn
     *
     * @param callable(): TReturn $callback
     * @return TReturn
     */
    private function withRetry(string $operation, callable $callback): mixed
    {
        $delay = max(0, $this->baseDelayMilliseconds);

        for ($attempt = 0; $attempt < $this->maxAttempts; $attempt++) {
            try {
                return $callback();
            } catch (Throwable $throwable) {
                if (! $this->shouldRetry($throwable) || $attempt === $this->maxAttempts - 1) {
                    throw $throwable;
                }

                Log::warning('Transient Redis failure encountered; retrying operation.', [
                    'operation' => $operation,
                    'attempt' => $attempt + 1,
                    'max_attempts' => $this->maxAttempts,
                    'exception' => get_class($throwable),
                    'message' => $throwable->getMessage(),
                ]);

                if ($delay > 0) {
                    ($this->sleep)($delay);
                }

                $delay = $delay > 0
                    ? min($this->maxDelayMilliseconds, $delay * 2)
                    : min($this->maxDelayMilliseconds, max(0, $this->baseDelayMilliseconds));
            }
        }

        throw new RuntimeException('Retry logic exhausted without executing callback.');
    }

    private function shouldRetry(Throwable $throwable): bool
    {
        if (class_exists('RedisException') && $throwable instanceof RedisException) {
            return true;
        }

        if (class_exists('RedisClusterException') && $throwable instanceof RedisClusterException) {
            return true;
        }

        if (class_exists('Predis\Connection\ConnectionException') &&
            $throwable instanceof \Predis\Connection\ConnectionException) {
            return true;
        }

        return false;
    }
}
