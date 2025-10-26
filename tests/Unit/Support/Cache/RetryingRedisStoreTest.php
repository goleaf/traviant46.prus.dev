<?php

declare(strict_types=1);

use App\Support\Cache\RetryingRedisStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Redis\Factory;
use Illuminate\Redis\Connections\Connection;

if (! class_exists('RedisException')) {
    class RedisException extends RuntimeException {}
}

it('retries transient failures and succeeds once the store recovers', function (): void {
    $connection = new RetryingRedisStoreTestFakeRedisConnection([
        'setex' => 2,
        'get' => 1,
    ]);

    $store = new RedisStore(
        new RetryingRedisStoreTestFakeRedisFactory($connection),
        '',
        'default',
    );

    $retryingStore = new RetryingRedisStore($store, 3, 0, 0);

    expect($retryingStore->put('foo', 'bar', 60))->toBeTrue();
    expect($retryingStore->get('foo'))->toBe('bar');
});

it('throws the last exception when retry attempts are exhausted', function (): void {
    $connection = new RetryingRedisStoreTestFakeRedisConnection([
        'setex' => 3,
    ]);

    $store = new RedisStore(
        new RetryingRedisStoreTestFakeRedisFactory($connection),
        '',
        'default',
    );

    $retryingStore = new RetryingRedisStore($store, 2, 0, 0);

    $retryingStore->put('foo', 'bar', 60);
})->throws(RedisException::class);

it('does not retry when a non-transient exception is thrown', function (): void {
    $connection = new RetryingRedisStoreTestFakeRedisConnection([
        'setex' => [new RuntimeException('critical failure')],
    ]);

    $store = new RedisStore(
        new RetryingRedisStoreTestFakeRedisFactory($connection),
        '',
        'default',
    );

    $retryingStore = new RetryingRedisStore($store, 3, 0, 0);

    $retryingStore->put('foo', 'bar', 60);
})->throws(RuntimeException::class);

final class RetryingRedisStoreTestFakeRedisFactory implements Factory
{
    public function __construct(private readonly RetryingRedisStoreTestFakeRedisConnection $connection) {}

    public function connection($name = null)
    {
        return $this->connection;
    }
}

final class RetryingRedisStoreTestFakeRedisConnection extends Connection
{
    /**
     * @param array<string, int|array<int, Throwable>> $failures
     */
    public function __construct(private array $failures = [], private array $store = []) {}

    public function createSubscription($channels, Closure $callback, $method = 'subscribe')
    {
        //
    }

    public function setex($key, $seconds, $value): bool
    {
        $this->maybeFail(__FUNCTION__);
        $this->store[$key] = $value;

        return true;
    }

    public function get($key)
    {
        $this->maybeFail(__FUNCTION__);

        return $this->store[$key] ?? null;
    }

    public function mget($keys): array
    {
        $this->maybeFail(__FUNCTION__);

        return array_map(fn ($key) => $this->store[$key] ?? null, $keys);
    }

    public function set($key, $value): bool
    {
        $this->maybeFail(__FUNCTION__);
        $this->store[$key] = $value;

        return true;
    }

    public function incrby($key, $value)
    {
        $this->maybeFail(__FUNCTION__);
        $this->store[$key] = ($this->store[$key] ?? 0) + $value;

        return $this->store[$key];
    }

    public function del($key): int
    {
        $this->maybeFail(__FUNCTION__);
        unset($this->store[$key]);

        return 1;
    }

    public function flushdb(): bool
    {
        $this->maybeFail(__FUNCTION__);
        $this->store = [];

        return true;
    }

    public function multi()
    {
        $this->maybeFail(__FUNCTION__);

        return $this;
    }

    public function exec(): bool
    {
        $this->maybeFail(__FUNCTION__);

        return true;
    }

    public function eval($script, $keysOrCount, ...$values)
    {
        $this->maybeFail(__FUNCTION__);

        $key = $values[0] ?? null;
        $value = $values[1] ?? null;

        if (! isset($this->store[$key])) {
            $this->store[$key] = $value;

            return 1;
        }

        return 0;
    }

    private function maybeFail(string $operation): void
    {
        if (! array_key_exists($operation, $this->failures)) {
            return;
        }

        $definition = &$this->failures[$operation];

        if (is_int($definition)) {
            if ($definition <= 0) {
                return;
            }

            $definition--;

            throw new RedisException(sprintf('Transient failure for %s', $operation));
        }

        if (is_array($definition) && $definition !== []) {
            $throwable = array_shift($definition);

            if ($throwable instanceof Throwable) {
                throw $throwable;
            }

            throw new RuntimeException('Invalid failure definition provided.');
        }
    }
}
