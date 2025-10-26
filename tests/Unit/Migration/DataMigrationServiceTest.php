<?php

namespace Tests\Unit\Migration;

use App\Services\Migration\DataMigrationReport;
use App\Services\Migration\DataMigrationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class DataMigrationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('migration.features', []);

        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('username');
            $table->string('email');
        });

        Config::set('database.connections.legacy', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        DB::purge('legacy');

        Schema::connection('legacy')->dropIfExists('legacy_users');
        Schema::connection('legacy')->create('legacy_users', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('username');
            $table->string('email');
        });
    }

    public function test_run_migrates_tables_using_upsert(): void
    {
        DB::connection('legacy')->table('legacy_users')->insert([
            ['id' => 1, 'username' => 'alice', 'email' => 'alice@example.test'],
            ['id' => 2, 'username' => 'bob', 'email' => 'bob@example.test'],
        ]);

        Config::set('migration.features.accounts.tables', [
            [
                'source' => 'legacy_users',
                'target' => 'users',
                'primary_key' => 'id',
                'mode' => 'upsert',
                'unique_by' => ['id'],
                'chunk' => 1,
            ],
        ]);

        $service = new DataMigrationService();
        $report = $service->run('accounts', 'legacy', config('database.default'));

        $this->assertInstanceOf(DataMigrationReport::class, $report);
        $this->assertSame(2, DB::table('users')->count());
        $this->assertSame(2, $report->tables['users']['rows_written']);
        $this->assertNotEmpty($report->events);
        $this->assertSame('table_complete', $report->events[2]['name'] ?? null);
    }

    public function test_run_truncates_target_and_applies_transform(): void
    {
        DB::table('users')->insert([
            ['id' => 99, 'username' => 'legacy', 'email' => 'legacy@example.test'],
        ]);

        DB::connection('legacy')->table('legacy_users')->insert([
            ['id' => 3, 'username' => 'charlie', 'email' => 'charlie@legacy.test'],
            ['id' => 4, 'username' => 'danielle', 'email' => 'danielle@legacy.test'],
        ]);

        Config::set('migration.features.accounts.tables', [
            [
                'source' => 'legacy_users',
                'target' => 'users',
                'primary_key' => 'id',
                'chunk' => 50,
                'truncate' => true,
                'mode' => 'insert',
                'transform' => function (array $row): array {
                    return [
                        'id' => $row['id'],
                        'username' => strtoupper($row['username']),
                        'email' => $row['email'],
                    ];
                },
            ],
        ]);

        $service = new DataMigrationService();
        $report = $service->run('accounts', 'legacy', config('database.default'));

        $usernames = DB::table('users')->orderBy('id')->pluck('username')->all();

        $this->assertSame(['CHARLIE', 'DANIELLE'], $usernames);
        $this->assertTrue($report->tables['users']['truncated']);
        $this->assertSame(2, $report->tables['users']['rows_written']);
    }

    public function test_run_throws_when_feature_configuration_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DataMigrationService())->run('missing', 'legacy', config('database.default'));
    }
}
