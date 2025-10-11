<?php

use App\Services\Maintenance\ReportCleanupService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    $databasePath = database_path('legacy_reports.sqlite');
    if (file_exists($databasePath)) {
        unlink($databasePath);
    }

    touch($databasePath);

    Config::set('database.connections.legacy', [
        'driver' => 'sqlite',
        'database' => $databasePath,
        'prefix' => '',
        'foreign_key_constraints' => false,
    ]);

    Config::set('maintenance.connections.legacy', 'legacy');

    Schema::connection('legacy')->dropIfExists('ndata');

    Schema::connection('legacy')->create('ndata', function (Blueprint $table): void {
        $table->increments('id');
        $table->unsignedInteger('uid')->default(0);
        $table->unsignedInteger('archive')->default(0);
        $table->unsignedInteger('deleted')->default(0);
        $table->unsignedInteger('non_deletable')->default(0);
        $table->unsignedInteger('type')->default(0);
        $table->unsignedInteger('losses')->default(0);
        $table->unsignedInteger('time')->default(0);
    });
});

it('removes soft deleted and expired reports', function (): void {
    Carbon::setTestNow('2025-01-01 00:00:00');

    Config::set('maintenance.reports.batch', 100);
    Config::set('maintenance.reports.deleted_retention_seconds', 3600);
    Config::set('maintenance.reports.retention_seconds', 7200);
    Config::set('maintenance.reports.remove_low_losses', true);
    Config::set('maintenance.reports.low_loss_types', [1, 4]);
    Config::set('maintenance.reports.low_loss_grace_seconds', 300);
    Config::set('maintenance.reports.loss_threshold_percent', 30);

    $now = now()->timestamp;

    DB::connection('legacy')->table('ndata')->insert([
        ['uid' => 1, 'archive' => 0, 'deleted' => 0, 'non_deletable' => 0, 'time' => $now - 10, 'type' => 0, 'losses' => 0],
        ['uid' => 5, 'archive' => 0, 'deleted' => 1, 'non_deletable' => 0, 'time' => $now - 4000, 'type' => 0, 'losses' => 0],
        ['uid' => 6, 'archive' => 0, 'deleted' => 0, 'non_deletable' => 0, 'time' => $now - 8000, 'type' => 0, 'losses' => 0],
        ['uid' => 7, 'archive' => 0, 'deleted' => 0, 'non_deletable' => 0, 'time' => $now - 400, 'type' => 1, 'losses' => 10],
        ['uid' => 8, 'archive' => 1, 'deleted' => 0, 'non_deletable' => 0, 'time' => $now - 8000, 'type' => 0, 'losses' => 0],
        ['uid' => 9, 'archive' => 0, 'deleted' => 0, 'non_deletable' => 1, 'time' => $now - 8000, 'type' => 0, 'losses' => 0],
    ]);

    $service = app(ReportCleanupService::class);
    $result = $service->handle();

    expect($result['deleted_soft_deleted'])->toBeGreaterThanOrEqual(1);
    expect($result['deleted_unarchived'])->toBeGreaterThanOrEqual(1);
    expect($result['deleted_low_loss'])->toBeGreaterThanOrEqual(1);

    $remainingIds = DB::connection('legacy')->table('ndata')->orderBy('uid')->pluck('uid')->all();

    expect($remainingIds)->toBe([8, 9]);
});

afterEach(function (): void {
    Carbon::setTestNow();
});
