<?php

declare(strict_types=1);

namespace App\Providers;

use App\Console\Commands\SecuredKeyGenerateCommand;
use App\Exceptions\QueueBackpressureExceededException;
use App\Models\Activation;
use App\Models\SitterDelegation;
use App\Models\User;
use App\Observers\ActivationObserver;
use App\Observers\SitterDelegationObserver;
use App\Observers\UserObserver;
use App\Services\Provisioning\InfrastructureApiClient;
use App\Services\Security\IpAnonymizer;
use App\Services\Security\MultiAccountRules;
use App\Support\Cache\RetryingRedisStore;
use App\Support\Http\SitterSessionContext as HttpSitterSessionContext;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Console\KeyGenerateCommand;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use PDOException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(InfrastructureApiClient::class, function ($app) {
            return new InfrastructureApiClient(
                $app['config']->get('provisioning.infrastructure', []),
            );
        });

        $this->app->singleton(KeyGenerateCommand::class, function ($app) {
            return tap(new SecuredKeyGenerateCommand, static fn ($command) => $command->setLaravel($app));
        });

        $this->app->singleton('command.key.generate', function ($app) {
            return $app->make(KeyGenerateCommand::class);
        });

        $this->app->singleton(IpAnonymizer::class, function ($app): IpAnonymizer {
            return new IpAnonymizer(
                $app['config']->get('privacy.ip', []),
            );
        });

        $this->app->singleton(MultiAccountRules::class, function ($app): MultiAccountRules {
            return new MultiAccountRules(
                $app['config']->get('multiaccount.allowlist', [
                    'ip_addresses' => [],
                    'cidr_ranges' => [],
                    'device_hashes' => [],
                ]),
                $app['config']->get('multiaccount.vpn_indicators', []),
            );
        });

        $this->app->singleton(
            \App\Support\Queue\CircuitBreaker::class,
            function ($app) {
                return new \App\Support\Queue\CircuitBreaker(
                    $app->make(CacheFactory::class),
                    $app->make(ConfigRepository::class),
                );
            },
        );

        if (! $this->app->bound(SessionFactoryContract::class)) {
            $this->app->alias('session', SessionFactoryContract::class);
        }

        if (! $this->app->bound(SessionContract::class)) {
            $this->app->alias('session.store', SessionContract::class);
        }

        $this->app->scoped(HttpSitterSessionContext::class, fn ($app): HttpSitterSessionContext => new HttpSitterSessionContext($app['request']));

        $this->app->afterResolving('cache', function (CacheManager $manager, $app): void {
            $manager->extend('redis', function ($app, array $config) use ($manager) {
                $connection = $config['connection'] ?? 'default';
                $prefix = $config['prefix'] ?? $app['config']->get('cache.prefix', '');
                $lockConnection = $config['lock_connection'] ?? $connection;

                $store = (new RedisStore($app['redis'], $prefix, $connection))
                    ->setLockConnection($lockConnection);

                $retryConfig = $app['config']->get('cache.retry', []);

                $attempts = max(1, (int) ($retryConfig['attempts'] ?? 3));
                $baseDelay = max(0, (int) ($retryConfig['backoff']['base'] ?? 50));
                $maxDelay = max($baseDelay, (int) ($retryConfig['backoff']['cap'] ?? 1000));

                return $manager->repository(
                    new RetryingRedisStore($store, $attempts, $baseDelay, $maxDelay),
                    $config,
                );
            });
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
        Activation::observe(ActivationObserver::class);
        SitterDelegation::observe(SitterDelegationObserver::class);

        if (method_exists($this, 'registerQueueBackpressure')) {
            $this->registerQueueBackpressure();
        }

        RateLimiter::for('sitter-mutations', function (Request $request): array {
            $limits = config('security.rate_limits.sitter_mutations', []);

            $perMinute = max(1, (int) ($limits['per_minute'] ?? 6));
            $perHour = max($perMinute, (int) ($limits['per_hour'] ?? 30));

            $userId = optional($request->user())->getAuthIdentifier();
            $key = $userId !== null ? 'sitter:'.$userId : 'sitter-ip:'.$request->ip();

            return [
                Limit::perMinute($perMinute)->by($key),
                Limit::perHour($perHour)->by($key),
            ];
        });

        RateLimiter::for('admin-actions', function (Request $request): array {
            $limits = config('security.rate_limits.admin_actions', []);

            $perMinute = max(1, (int) ($limits['per_minute'] ?? 30));
            $perHour = max($perMinute, (int) ($limits['per_hour'] ?? 120));

            $userId = optional($request->user())->getAuthIdentifier();
            $key = $userId !== null ? 'admin:'.$userId : 'admin-ip:'.$request->ip();

            return [
                Limit::perMinute($perMinute)->by($key),
                Limit::perHour($perHour)->by($key),
            ];
        });

        $this->registerPerUserThrottle(
            'game.market',
            config('security.rate_limits.market', []),
            'Too many marketplace actions. Try again in :seconds seconds.',
        );

        $this->registerPerUserThrottle(
            'game.send',
            config('security.rate_limits.send', []),
            'Too many send operations. Try again in :seconds seconds.',
        );

        $this->registerPerUserThrottle(
            'game.messages',
            config('security.rate_limits.messages', []),
            'Too many messages are being sent. Try again in :seconds seconds.',
        );
    }

    private function registerPerUserThrottle(string $limiter, array $limits, string $message): void
    {
        RateLimiter::for($limiter, function (Request $request) use ($limits, $message, $limiter) {
            $perMinute = max(0, (int) ($limits['per_minute'] ?? 0));
            $perHour = max(0, (int) ($limits['per_hour'] ?? 0));

            $userId = optional($request->user())->getAuthIdentifier();
            $key = $userId !== null ? 'user:'.$userId : 'ip:'.$request->ip();

            $configuredLimits = [];

            if ($perMinute > 0) {
                $configuredLimits[] = Limit::perMinute($perMinute)
                    ->by('minute:'.$key)
                    ->response(function () use ($limiter, $key, $message, $perMinute) {
                        $cacheKey = $this->limiterCacheKey($limiter, 'minute:'.$key);
                        $seconds = (int) RateLimiter::availableIn($cacheKey);

                        if ($seconds <= 0) {
                            $seconds = 60;
                        }

                        return ThrottleResponse::json(
                            strtr($message, [':seconds' => (string) $seconds]),
                            $seconds,
                            $perMinute,
                        );
                    });
            }

            if ($perHour > 0) {
                $perHour = max($perHour, $perMinute);

                $configuredLimits[] = Limit::perHour($perHour)
                    ->by('hour:'.$key)
                    ->response(function () use ($limiter, $key, $message, $perHour) {
                        $cacheKey = $this->limiterCacheKey($limiter, 'hour:'.$key);
                        $seconds = (int) RateLimiter::availableIn($cacheKey);

                        if ($seconds <= 0) {
                            $seconds = 3600;
                        }

                        return ThrottleResponse::json(
                            strtr($message, [':seconds' => (string) $seconds]),
                            $seconds,
                            $perHour,
                        );
                    });
            }

            if ($configuredLimits === []) {
                return Limit::none();
            }

            return $configuredLimits;
        });
    }

    private function limiterCacheKey(string $limiter, string $key): string
    {
        return md5($limiter.$key);
    }

    protected function registerQueueBackpressure(): void
    {
        if (! $this->app->bound('queue')) {
            return;
        }

        $breakerClass = \App\Support\Queue\CircuitBreaker::class;

        if (! class_exists($breakerClass)) {
            return;
        }

        /** @var \App\Support\Queue\CircuitBreaker $breaker */
        $breaker = $this->app->make($breakerClass);

        Queue::before(function (JobProcessing $event) use ($breaker): void {
            $maxAttempts = max(0, (int) config('queue.backpressure.max_attempts', 0));

            if ($maxAttempts > 0 && $event->job->attempts() > $maxAttempts) {
                $event->job->fail(
                    QueueBackpressureExceededException::attempts(
                        $event->job->resolveName(),
                        $maxAttempts,
                    ),
                );

                return;
            }

            if ($breaker->isOpen('database')) {
                $delay = max(5, $breaker->cooldownRemaining('database'));
                $event->job->release($delay);
                $event->job->delete();
            }
        });

        Queue::after(function (JobProcessed $event) use ($breaker): void {
            if (! $breaker->isOpen('database')) {
                $breaker->recordSuccess('database');
            }
        });

        Queue::failing(function (JobFailed $event) use ($breaker): void {
            if ($this->isDatabaseException($event->exception)) {
                $breaker->recordFailure('database');
            }
        });
    }

    private function isDatabaseException(Throwable $throwable): bool
    {
        if ($throwable instanceof QueryException || $throwable instanceof PDOException) {
            return true;
        }

        $previous = $throwable->getPrevious();

        return $previous instanceof Throwable && $this->isDatabaseException($previous);
    }
}
use Illuminate\Support\Facades\View;
