<?php

namespace App\Providers;

use App\Support\Travian\LegacyConfigRepository;
use App\ValueObjects\Travian\GlobalCacheKey;
use App\ValueObjects\Travian\LegacyPaths;
use App\ValueObjects\Travian\MapSize;
use App\ValueObjects\Travian\PaymentPaths;
use App\ValueObjects\Travian\TaskWorkerRuntime;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;

final class LegacyTravianServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LegacyConfigRepository::class, function ($app): LegacyConfigRepository {
            /** @var CacheFactory $cacheFactory */
            $cacheFactory = $app->make(CacheFactory::class);
            /** @var DatabaseManager $databaseManager */
            $databaseManager = $app->make(DatabaseManager::class);
            /** @var ConfigRepository $config */
            $config = $app->make(ConfigRepository::class);

            return new LegacyConfigRepository(
                $cacheFactory,
                $databaseManager,
                $config->get('travian.connection', []),
                $config->get('travian.static', []),
                $config->get('travian.settings', []),
                $config->get('travian.runtime', []),
            );
        });

        $this->app->singleton(LegacyPaths::class, function ($app): LegacyPaths {
            /** @var ConfigRepository $config */
            $config = $app->make(ConfigRepository::class);
            $paths = $config->get('travian.runtime.paths', []);

            return new LegacyPaths(
                $paths['root'] ?? base_path('_travian/main_script/'),
                $paths['include'] ?? base_path('_travian/main_script/include/'),
                $paths['public_internal'] ?? base_path('_travian/main_script/copyable/public/'),
                $paths['resources'] ?? base_path('_travian/main_script/include/resources/'),
                $paths['locale'] ?? base_path('_travian/main_script/include/resources/Translation/'),
                $paths['templates'] ?? base_path('_travian/main_script/include/resources/Templates/'),
            );
        });

        $this->app->singleton(GlobalCacheKey::class, function ($app): GlobalCacheKey {
            /** @var ConfigRepository $config */
            $config = $app->make(ConfigRepository::class);
            $value = (string) ($config->get('travian.runtime.global_cache_key') ?? get_current_user());

            return new GlobalCacheKey($value);
        });

        $this->app->singleton(MapSize::class, function ($app): MapSize {
            $config = $app->make(LegacyConfigRepository::class)->get();
            $value = (int) ($config->dynamic->map_size ?? 0);

            return new MapSize($value);
        });

        $this->app->singleton(TaskWorkerRuntime::class, function ($app): TaskWorkerRuntime {
            /** @var ConfigRepository $config */
            $config = $app->make(ConfigRepository::class);
            $runtime = $config->get('travian.runtime.task_worker', []);

            return new TaskWorkerRuntime(
                $runtime['root'] ?? base_path('_travian/TaskWorker/'),
                $runtime['include'] ?? base_path('_travian/TaskWorker/include/'),
                $runtime['php_binary'] ?? PHP_BINARY,
            );
        });

        $this->app->singleton(PaymentPaths::class, function ($app): PaymentPaths {
            /** @var ConfigRepository $config */
            $config = $app->make(ConfigRepository::class);
            $paths = $config->get('travian.runtime.payment', []);

            return new PaymentPaths(
                $paths['root'] ?? base_path('_travian/sections/payment/'),
                $paths['include'] ?? base_path('_travian/sections/payment/include/'),
                $paths['locale'] ?? base_path('_travian/sections/payment/include/Locale/'),
            );
        });
    }

    public function boot(): void
    {
        /** @var ConfigRepository $configRepository */
        $configRepository = $this->app->make(ConfigRepository::class);

        if (
            $this->app->environment('testing')
            || (bool) $configRepository->get('travian.runtime.disable_bootstrap', false)
        ) {
            return;
        }

        /** @var LegacyConfigRepository $repository */
        $repository = $this->app->make(LegacyConfigRepository::class);
        $config = $repository->get();

        $configRepository->set('travian.dynamic', $config->dynamic?->toArray() ?? []);

        $dbConfig = $config->db ?? null;
        if (is_object($dbConfig) && method_exists($dbConfig, 'toArray')) {
            $dbConfig = $dbConfig->toArray();
        }

        $configRepository->set('travian.db', $dbConfig ?? []);
    }
}
