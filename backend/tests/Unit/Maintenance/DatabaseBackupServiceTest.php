<?php

use App\Services\Maintenance\DatabaseBackupService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    $databasePath = database_path('database.sqlite');
    if (! File::exists($databasePath)) {
        File::put($databasePath, '');
    }

    Config::set('database.default', 'sqlite');
    Config::set('database.connections.sqlite.database', $databasePath);

    Schema::dropIfExists('samples');

    Schema::create('samples', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('name');
    });

    DB::table('samples')->truncate();
    DB::table('samples')->insert([
        ['name' => 'Alpha'],
        ['name' => 'Beta'],
    ]);
});

it('creates a json backup of the configured database', function (): void {
    $directory = storage_path('framework/testing/backups');
    if (File::exists($directory)) {
        File::deleteDirectory($directory);
    }

    Config::set('maintenance.backup.directory', $directory);
    Config::set('maintenance.backup.connection', 'sqlite');

    $service = app(DatabaseBackupService::class);
    $path = $service->run();

    expect(File::exists($path))->toBeTrue();

    $data = json_decode(File::get($path), true);

    expect($data['tables']['samples'][0]['name'])->toBe('Alpha');
    expect($data['tables']['samples'][1]['name'])->toBe('Beta');
});

afterEach(function (): void {
    $directory = storage_path('framework/testing/backups');
    if (File::exists($directory)) {
        File::deleteDirectory($directory);
    }
});
