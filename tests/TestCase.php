<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');
        config()->set('travian.runtime.cache_store', 'array');
        config()->set('database.default', env('DB_CONNECTION', 'sqlite'));
        config()->set('queue.default', 'sync');

        if (! $this->app->bound('session')) {
            config()->set('session.driver', 'array');
            $this->app->register(\Illuminate\Session\SessionServiceProvider::class);
        }

        $session = $this->app->make('session');

        if (method_exists($session, 'start') && ! $session->isStarted()) {
            $session->start();
        }

        if (! $this->app->bound('bugsnag')) {
            $this->app->singleton('bugsnag', fn () => new class
            {
                public function __call(string $name, array $arguments): void
                {
                    // Silence Bugsnag facade during tests.
                }
            });
        }
    }
}
