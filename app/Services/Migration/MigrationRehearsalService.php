<?php

declare(strict_types=1);

namespace App\Services\Migration;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrationRehearsalService
{
    public function run(string $feature, string $primaryConnection, string $legacyConnection, bool $triggerRegression = true): MigrationRehearsalReport
    {
        $report = new MigrationRehearsalReport($feature, $primaryConnection, $legacyConnection);

        $this->assertConnection($legacyConnection);
        $this->assertConnection($primaryConnection);
        $report->addEvent('database_connections', [
            'primary' => $primaryConnection,
            'legacy' => $legacyConnection,
        ]);

        $report->migrationPreview = $this->previewMigrations($primaryConnection);
        $report->addEvent('migration_preview', [
            'statements' => $report->migrationPreview,
        ]);

        $featureKey = sprintf('features.%s.source', $feature);
        $previousSource = Config::get($featureKey, 'legacy');
        Config::set($featureKey, 'laravel');

        Log::info('migration.rehearsal.feature_switch', [
            'feature' => $feature,
            'mode' => 'laravel',
            'previous' => $previousSource,
        ]);
        $report->addEvent('feature_switch', [
            'feature' => $feature,
            'from' => $previousSource,
            'to' => 'laravel',
        ]);

        if ($triggerRegression) {
            $report->regressionSummary = $this->runRegressionSuite();
            $report->addEvent('regression_suite', [
                'summary' => $report->regressionSummary,
            ]);
        }

        Config::set($featureKey, $previousSource);

        return $report;
    }

    protected function assertConnection(string $name): void
    {
        DB::connection($name)->getPdo();
    }

    protected function previewMigrations(string $connection): string
    {
        Artisan::call('migrate', [
            '--database' => $connection,
            '--pretend' => true,
            '--force' => true,
        ]);

        return trim(Artisan::output());
    }

    protected function runRegressionSuite(): string
    {
        Artisan::call('test', [
            '--testsuite' => 'Feature',
            '--filter' => 'LegacyLoginParity',
        ]);

        return trim(Artisan::output());
    }
}
