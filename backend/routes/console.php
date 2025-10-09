<?php

use App\Services\Migration\MigrationRehearsalService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('migration:rehearse {--feature=hero} {--database?} {--legacy=legacy} {--no-regression}', function (MigrationRehearsalService $service) {
    $feature = $this->option('feature') ?? 'hero';
    $database = $this->option('database') ?? config('database.default');
    $legacy = $this->option('legacy') ?? 'legacy';
    $triggerRegression = ! $this->option('no-regression');

    $report = $service->run($feature, $database, $legacy, $triggerRegression);

    $this->info(sprintf('Rehearsal report for feature [%s]:', $feature));
    foreach ($report->events as $event) {
        $this->line(sprintf('- %s: %s', $event['name'], json_encode($event['context'])));
    }

    if ($report->migrationPreview !== '') {
        $this->line('Migration preview:');
        $this->line($report->migrationPreview);
    }

    if ($report->regressionSummary !== '') {
        $this->line('Regression summary:');
        $this->line($report->regressionSummary);
    }
})->purpose('Run a migration rehearsal and regression parity check.');
