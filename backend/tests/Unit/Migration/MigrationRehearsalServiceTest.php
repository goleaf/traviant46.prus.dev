<?php

namespace Tests\Unit\Migration;

use App\Services\Migration\MigrationRehearsalReport;
use App\Services\Migration\MigrationRehearsalService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class MigrationRehearsalServiceTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function test_run_without_regression_executes_pretend_migration_and_feature_toggle(): void
    {
        Config::set('features.hero.source', 'legacy');

        $legacyConnection = Mockery::mock();
        $legacyConnection->shouldReceive('getPdo')->once();
        $primaryConnection = Mockery::mock();
        $primaryConnection->shouldReceive('getPdo')->once();

        DB::shouldReceive('connection')->with('legacy')->andReturn($legacyConnection);
        DB::shouldReceive('connection')->with('mysql')->andReturn($primaryConnection);

        Artisan::shouldReceive('call')->once()->with('migrate', Mockery::on(function ($options) {
            return $options['--database'] === 'mysql'
                && $options['--pretend'] === true
                && $options['--force'] === true;
        }));
        Artisan::shouldReceive('output')->once()->andReturn("CREATE TABLE users (...);\n");

        Log::spy();

        $service = new MigrationRehearsalService();
        $report = $service->run('hero', 'mysql', 'legacy', triggerRegression: false);

        $this->assertInstanceOf(MigrationRehearsalReport::class, $report);
        $this->assertSame('CREATE TABLE users (...);', $report->migrationPreview);
        $this->assertSame('', $report->regressionSummary);
        $this->assertSame('legacy', config('features.hero.source'));

        Log::shouldHaveLogged('info', function (string $message, array $context) {
            return $message === 'migration.rehearsal.feature_switch'
                && $context['feature'] === 'hero'
                && $context['mode'] === 'laravel';
        });

        $this->assertNotEmpty($report->events);
        $this->assertSame('database_connections', $report->events[0]['name']);
    }
}
