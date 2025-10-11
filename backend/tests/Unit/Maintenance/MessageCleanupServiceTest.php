<?php

use App\Services\Maintenance\MessageCleanupService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    $databasePath = database_path('legacy_messages.sqlite');
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

    Schema::connection('legacy')->dropIfExists('mdata');

    Schema::connection('legacy')->create('mdata', function (Blueprint $table): void {
        $table->increments('id');
        $table->unsignedInteger('uid');
        $table->unsignedInteger('to_uid');
        $table->boolean('viewed')->default(false);
        $table->boolean('archived')->default(false);
        $table->unsignedInteger('time')->default(0);
    });
});

it('removes viewed messages older than the retention window', function (): void {
    Carbon::setTestNow('2025-01-01 00:00:00');

    Config::set('maintenance.messages.retention_seconds', 3600);
    Config::set('maintenance.messages.batch', 50);

    $now = now()->timestamp;

    DB::connection('legacy')->table('mdata')->insert([
        ['uid' => 1, 'to_uid' => 2, 'viewed' => 1, 'time' => $now - 7200],
        ['uid' => 2, 'to_uid' => 1, 'viewed' => 0, 'time' => $now - 7200],
        ['uid' => 3, 'to_uid' => 1, 'viewed' => 1, 'time' => $now - 1800],
    ]);

    $service = app(MessageCleanupService::class);
    $result = $service->handle();

    expect($result['deleted'])->toBe(1);
    expect(DB::connection('legacy')->table('mdata')->count())->toBe(2);
});

afterEach(function (): void {
    Carbon::setTestNow();
});
