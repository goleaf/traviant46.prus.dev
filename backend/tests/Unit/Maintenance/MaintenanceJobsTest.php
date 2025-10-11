<?php

use App\Jobs\BackupDatabase;
use App\Jobs\CleanupInactivePlayers;
use App\Jobs\CleanupOldMessages;
use App\Jobs\CleanupOldReports;
use App\Services\Maintenance\DatabaseBackupService;
use App\Services\Maintenance\InactivePlayerCleanupService;
use App\Services\Maintenance\MessageCleanupService;
use App\Services\Maintenance\ReportCleanupService;
use Illuminate\Support\Facades\Log;

afterEach(function (): void {
    \Mockery::close();
});

it('delegates old message cleanup to the service', function (): void {
    Log::spy();

    $service = \Mockery::mock(MessageCleanupService::class);
    $service->shouldReceive('handle')->once()->andReturn(['deleted' => 5]);

    (new CleanupOldMessages())->handle($service);

    Log::shouldHaveReceived('info')->once();
});

it('delegates old report cleanup to the service', function (): void {
    Log::spy();

    $service = \Mockery::mock(ReportCleanupService::class);
    $service->shouldReceive('handle')->once()->andReturn([
        'deleted_soft_deleted' => 1,
        'deleted_unarchived' => 2,
        'deleted_low_loss' => 0,
    ]);

    (new CleanupOldReports())->handle($service);

    Log::shouldHaveReceived('info')->once();
});

it('delegates inactive player cleanup to the service', function (): void {
    Log::spy();

    $service = \Mockery::mock(InactivePlayerCleanupService::class);
    $service->shouldReceive('handle')->once()->andReturn(['scheduled' => 3, 'purged' => 1]);

    (new CleanupInactivePlayers())->handle($service);

    Log::shouldHaveReceived('info')->once();
});

it('invokes the database backup service', function (): void {
    $service = \Mockery::mock(DatabaseBackupService::class);
    $service->shouldReceive('run')->once()->andReturn('/tmp/backup.json');

    (new BackupDatabase())->handle($service);
});
