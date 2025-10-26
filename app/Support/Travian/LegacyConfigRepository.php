<?php

namespace App\Support\Travian;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\QueryException;

final class LegacyConfigRepository
{
    private ?LegacyConfigNode $config = null;

    public function __construct(
        private readonly CacheFactory $cacheFactory,
        private readonly DatabaseManager $databaseManager,
        private readonly array $connectionConfig,
        private readonly array $staticConfig,
        private readonly array $settingsConfig,
        private readonly array $runtimeConfig,
    ) {}

    public function warm(): void
    {
        $this->get();
    }

    public function get(): LegacyConfigNode
    {
        if ($this->config instanceof LegacyConfigNode) {
            return $this->config;
        }

        $dynamic = $this->loadDynamicConfig();

        $data = [
            'db' => $this->connectionConfig['database'] ?? [],
            'dynamic' => $dynamic,
        ];

        foreach ($this->settingsConfig as $key => $value) {
            if ($key === 'timers') {
                $data['timers'] = $value;

                continue;
            }

            $data[$key] = $value;
        }

        $data['settings']['session_timeout'] = $this->settingsConfig['settings']['session_timeout']
            ?? $this->staticConfig['session_timeout']
            ?? 6 * 3600;

        $data['settings']['availableLanguages'] = $this->settingsConfig['settings']['availableLanguages'] ?? [];

        $data['settings']['default_language'] = $this->settingsConfig['settings']['default_language']
            ?? $this->staticConfig['default_language'] ?? 'us';

        $data['settings']['selectedLang'] = $this->settingsConfig['settings']['selectedLang']
            ?? $data['settings']['default_language'];

        $data['settings']['indexUrl'] = $this->settingsConfig['settings']['indexUrl']
            ?? $this->staticConfig['index_url'] ?? '';

        $data['settings']['global_css_class'] = $this->settingsConfig['settings']['global_css_class']
            ?? $this->staticConfig['global_css_class'] ?? '';

        $data['settings']['gameWorldUrl'] = $this->settingsConfig['settings']['gameWorldUrl']
            ?? $this->connectionConfig['game_world_url'] ?? '';

        $data['settings']['engine_filename'] = $this->settingsConfig['settings']['engine_filename']
            ?? $this->connectionConfig['engine_filename'] ?? '';

        $data['settings']['worldId'] = $this->settingsConfig['settings']['worldId']
            ?? $this->connectionConfig['world_id'] ?? '';

        $data['settings']['serverName'] = $this->settingsConfig['settings']['serverName']
            ?? $this->connectionConfig['server_name'] ?? '';

        $data['settings']['secure_hash_code'] = $this->settingsConfig['settings']['secure_hash_code']
            ?? $this->connectionConfig['secure_hash_code'] ?? '';

        $data['timers']['auto_reinstall'] = $this->settingsConfig['timers']['auto_reinstall']
            ?? $this->connectionConfig['auto_reinstall'] ?? false;

        if (! isset($data['game'])) {
            $data['game'] = [];
        }

        if (isset($dynamic['startTime'])) {
            $data['game']['start_time'] = $dynamic['startTime'];
        }

        if (isset($dynamic['worldUniqueId'])) {
            $data['settings']['worldUniqueId'] = $dynamic['worldUniqueId'];
        }

        if (isset($dynamic['map_size'])) {
            $data['game']['map_size'] = $dynamic['map_size'];
        }

        $this->config = new LegacyConfigNode($data);

        return $this->config;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadDynamicConfig(): array
    {
        $cacheKey = $this->runtimeConfig['world_config_cache_key'] ?? 'travian.world_config';
        $ttl = (int) ($this->runtimeConfig['world_config_ttl'] ?? 300);
        $cacheStore = $this->runtimeConfig['cache_store'] ?? null;

        $cache = $cacheStore
            ? $this->cacheFactory->store($cacheStore)
            : $this->cacheFactory->store();

        return $cache->remember($cacheKey, $ttl, function (): array {
            $connection = $this->databaseManager->connection();
            $schema = $connection->getSchemaBuilder();

            if ($schema === null || ! $schema->hasTable('config')) {
                return [];
            }

            try {
                $row = $connection->table('config')->first();
            } catch (QueryException $exception) {
                if (str_contains(strtolower($exception->getMessage()), 'no such table')) {
                    return [];
                }

                throw $exception;
            }

            if ($row === null) {
                return [];
            }

            return (array) $row;
        });
    }
}
