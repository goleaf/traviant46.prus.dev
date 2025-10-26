<?php

declare(strict_types=1);

use App\Jobs\VerifyDatabaseIntegrity;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite', [
        'driver' => 'sqlite',
        'database' => database_path('testing.sqlite'),
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);

    $databasePath = database_path('testing.sqlite');
    if (! file_exists($databasePath)) {
        touch($databasePath);
    }

    DB::purge();
    DB::reconnect();

    Schema::dropIfExists('health_checks');
    Schema::create('health_checks', function (Blueprint $table): void {
        $table->id();
        $table->timestamp('checked_at')->nullable();
    });
});

it('passes the integrity check for the default sqlite connection', function (): void {
    $job = new VerifyDatabaseIntegrity;

    expect(fn () => $job->handle())->not->toThrow();
});
