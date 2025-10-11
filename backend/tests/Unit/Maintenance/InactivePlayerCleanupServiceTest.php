<?php

use App\Services\Maintenance\InactivePlayerCleanupService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    $databasePath = database_path('legacy_inactive.sqlite');
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

    $tables = [
        'users' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('total_pop')->default(0);
            $table->unsignedInteger('total_villages')->default(0);
            $table->unsignedInteger('last_login_time')->default(0);
            $table->unsignedInteger('vacationActiveTil')->default(0);
            $table->unsignedTinyInteger('hidden')->default(0);
            $table->unsignedInteger('sit1Uid')->default(0);
            $table->unsignedInteger('sit2Uid')->default(0);
        },
        'deleting' => function (Blueprint $table): void {
            $table->unsignedInteger('uid')->primary();
            $table->unsignedInteger('time')->default(0);
        },
        'vdata' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('owner')->default(0);
        },
        'mdata' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
            $table->unsignedInteger('to_uid')->default(0);
        },
        'ndata' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
        },
        'hero' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
        },
        'inventory' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
        },
        'items' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
        },
        'adventure' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
        },
        'notes' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
            $table->unsignedInteger('to_uid')->default(0);
        },
        'friendlist' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
            $table->unsignedInteger('to_uid')->default(0);
        },
        'ignoreList' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
            $table->unsignedInteger('ignore_id')->default(0);
        },
        'autoExtend' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
        },
        'auction' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
        },
        'bids' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
        },
        'activation_progress' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
        },
        'banQueue' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
        },
        'banHistory' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
        },
        'player_references' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('ref_uid')->default(0);
            $table->unsignedInteger('uid')->default(0);
        },
        'medal' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
        },
        'newproc' => function (Blueprint $table): void {
            $table->unsignedInteger('uid')->primary();
        },
        'mapflag' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
        },
        'links' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
        },
        'forum_open_players' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
        },
        'accounting' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
        },
        'ali_invite' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
        },
        'alliance_bonus_upgrade_queue' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
        },
        'farmlist' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
        },
        'farmlist_last_reports' => function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('uid')->default(0);
        },
    ];

    foreach ($tables as $name => $callback) {
        Schema::connection('legacy')->dropIfExists($name);
        Schema::connection('legacy')->create($name, $callback);
    }
});

it('schedules and purges inactive players', function (): void {
    Carbon::setTestNow('2025-01-01 00:00:00');

    Config::set('maintenance.inactive_players.batch', 10);
    Config::set('maintenance.inactive_players.deletion_grace_seconds', 0);
    Config::set('maintenance.inactive_players.thresholds', [
        ['max_population' => null, 'inactive_seconds' => 3600],
    ]);

    $now = now()->timestamp;

    DB::connection('legacy')->table('users')->insert([
        ['id' => 3, 'total_pop' => 50, 'total_villages' => 1, 'last_login_time' => $now - 7200, 'vacationActiveTil' => 0, 'hidden' => 0, 'sit1Uid' => 0, 'sit2Uid' => 0],
        ['id' => 4, 'total_pop' => 50, 'total_villages' => 1, 'last_login_time' => $now - 1000, 'vacationActiveTil' => 0, 'hidden' => 0, 'sit1Uid' => 3, 'sit2Uid' => 0],
    ]);

    DB::connection('legacy')->table('vdata')->insert([
        ['owner' => 3],
    ]);

    DB::connection('legacy')->table('mdata')->insert([
        ['uid' => 3, 'to_uid' => 1],
    ]);

    DB::connection('legacy')->table('ndata')->insert([
        ['uid' => 3],
    ]);

    DB::connection('legacy')->table('notes')->insert([
        ['uid' => 3, 'to_uid' => 4],
    ]);

    DB::connection('legacy')->table('friendlist')->insert([
        ['uid' => 3, 'to_uid' => 4],
    ]);

    DB::connection('legacy')->table('ignoreList')->insert([
        ['uid' => 3, 'ignore_id' => 4],
    ]);

    $service = app(InactivePlayerCleanupService::class);
    $result = $service->handle();

    expect($result['scheduled'])->toBe(1);
    expect($result['purged'])->toBe(1);

    $user = DB::connection('legacy')->table('users')->where('id', 3)->first();
    expect($user->hidden)->toBe(1);
    expect($user->total_pop)->toBe(0);

    $villageOwner = DB::connection('legacy')->table('vdata')->value('owner');
    expect($villageOwner)->toBe(0);

    expect(DB::connection('legacy')->table('mdata')->count())->toBe(0);
    expect(DB::connection('legacy')->table('ndata')->count())->toBe(0);
    expect(DB::connection('legacy')->table('deleting')->count())->toBe(0);

    $sitter = DB::connection('legacy')->table('users')->where('id', 4)->first();
    expect($sitter->sit1Uid)->toBe(0);
});

afterEach(function (): void {
    Carbon::setTestNow();
});
