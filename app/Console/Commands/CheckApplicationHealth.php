<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Throwable;

class CheckApplicationHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'travian:health-check
        {--database : Only run the database connectivity checks}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a series of diagnostics to validate the TravianT stack.';

    public function handle(): int
    {
        $this->components->info('TravianT Diagnostics');
        $this->newLine();

        $databaseOnly = (bool) $this->option('database');
        $allPassed = true;

        if (! $databaseOnly) {
            $allPassed = $this->checkApplicationBootstrap() && $allPassed;
            $allPassed = $this->checkEnvironmentFile() && $allPassed;
            $allPassed = $this->checkRouteDefinitions() && $allPassed;
            $allPassed = $this->checkPublicAssets() && $allPassed;
            $allPassed = $this->checkStoragePermissions() && $allPassed;
        }

        $allPassed = $this->checkDatabaseConnection() && $allPassed;

        $this->newLine();

        if (! $allPassed) {
            $this->components->error('One or more diagnostics failed. Please address the issues above.');

            return self::FAILURE;
        }

        $this->components->success('All diagnostics passed. Your TravianT environment is ready.');

        return self::SUCCESS;
    }

    private function checkApplicationBootstrap(): bool
    {
        $this->components->info('• Verifying Laravel bootstrap');

        $this->line(sprintf('  Application: %s (%s)', config('app.name'), app()->version()));
        $this->line(sprintf('  Environment: %s', config('app.env')));

        return true;
    }

    private function checkDatabaseConnection(): bool
    {
        $this->components->info('• Checking database connection');

        try {
            $connection = DB::connection();
            $driver = $connection->getDriverName();
            $connection->getPdo();

            $tables = $this->listTables($connection);
            $tableCount = count($tables);

            $this->line(sprintf('  Connected using the "%s" driver.', $driver));
            $this->line(sprintf('  Found %d tables.', $tableCount));

            if ($tableCount > 0) {
                $sample = array_slice($tables, 0, 5);
                $this->line('  Sample tables: '.implode(', ', $sample).($tableCount > 5 ? '…' : ''));
            }

            return true;
        } catch (QueryException $exception) {
            $this->components->error('  Database query failed: '.$exception->getMessage());

            return false;
        } catch (Throwable $exception) {
            $this->components->error('  Unable to connect to the database: '.$exception->getMessage());

            return false;
        }
    }

    private function checkEnvironmentFile(): bool
    {
        $this->components->info('• Checking environment configuration');

        $path = base_path('.env');

        if (! File::exists($path)) {
            $this->components->error('  The .env file is missing. Copy .env.example to .env to continue.');

            return false;
        }

        $appKey = config('app.key');
        $appUrl = config('app.url');
        $issues = [];

        if (blank($appKey)) {
            $issues[] = 'APP_KEY is not set. Run "php artisan key:generate".';
        }

        if (blank($appUrl) || ! Str::startsWith($appUrl, ['http://', 'https://'])) {
            $issues[] = 'APP_URL is not set to a valid URL.';
        }

        if ($issues !== []) {
            foreach ($issues as $issue) {
                $this->components->error('  '.$issue);
            }

            return false;
        }

        $this->line('  Found .env with a valid APP_KEY and APP_URL configuration.');

        return true;
    }

    private function checkRouteDefinitions(): bool
    {
        $this->components->info('• Inspecting HTTP routes');

        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => in_array($route->uri(), ['/', 'login', 'register', 'up'], true))
            ->map(function ($route) {
                $methods = implode('|', $route->methods());

                return sprintf('%s %s', $methods, '/'.ltrim($route->uri(), '/'));
            })
            ->values();

        if ($routes->isEmpty()) {
            $this->components->error('  Expected core routes were not registered.');

            return false;
        }

        foreach ($routes as $route) {
            $this->line('  '.$route);
        }

        return true;
    }

    private function checkPublicAssets(): bool
    {
        $this->components->info('• Verifying public assets');

        $indexPath = public_path('index.php');

        if (! File::exists($indexPath) || ! File::isReadable($indexPath)) {
            $this->components->error('  public/index.php is missing or unreadable.');

            return false;
        }

        $this->line('  public/index.php is present and readable.');

        return true;
    }

    private function checkStoragePermissions(): bool
    {
        $this->components->info('• Checking storage permissions');

        $directories = [
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
        ];

        $failures = [];

        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                $failures[] = sprintf('  %s does not exist.', Str::after($directory, base_path().DIRECTORY_SEPARATOR));

                continue;
            }

            if (! is_writable($directory)) {
                $failures[] = sprintf('  %s is not writable.', Str::after($directory, base_path().DIRECTORY_SEPARATOR));
            }
        }

        if ($failures !== []) {
            foreach ($failures as $failure) {
                $this->components->error($failure);
            }

            return false;
        }

        $this->line('  Storage directories exist and are writable.');

        return true;
    }

    /**
     * @return list<string>
     */
    private function listTables(Connection $connection): array
    {
        return match ($connection->getDriverName()) {
            'sqlite' => collect($connection->select("select name from sqlite_master where type='table'"))
                ->pluck('name')
                ->filter()
                ->map(static fn ($name) => (string) $name)
                ->all(),
            'mysql', 'mariadb' => collect($connection->select('show tables'))
                ->map(static fn ($row) => (string) array_values((array) $row)[0])
                ->all(),
            'pgsql' => collect($connection->select("select tablename from pg_tables where schemaname = 'public'"))
                ->map(static fn ($row) => (string) ($row->tablename ?? $row->table_name ?? ''))
                ->filter()
                ->all(),
            default => [],
        };
    }
}
