<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\SitterAssignment;
use App\Models\SitterDelegation;
use App\Monitoring\Metrics\LogMetricRecorder;
use App\Monitoring\Metrics\MetricRecorder;
use App\Monitoring\Metrics\StatsdMetricRecorder;
use App\Observers\SitterDelegationObserver;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class MonitoringServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MetricRecorder::class, function ($app) {
            $config = $app['config']->get('monitoring.metrics', []);
            $driver = $config['driver'] ?? 'log';

            return match ($driver) {
                'statsd' => new StatsdMetricRecorder(
                    host: $config['statsd']['host'] ?? '127.0.0.1',
                    port: (int) ($config['statsd']['port'] ?? 8125),
                    namespace: $config['statsd']['namespace'] ?? 'travian',
                    failoverChannel: $config['statsd']['failover_log_channel'] ?? null,
                ),
                default => new LogMetricRecorder(
                    channel: $config['log']['channel'] ?? 'metrics',
                ),
            };
        });
    }

    public function boot(): void
    {
        $requestIdHeader = config('monitoring.request_id_header', 'X-Request-Id');

        Http::globalRequestMiddleware(function ($request, $next) use ($requestIdHeader) {
            if (Context::has('request_id')) {
                $request = $request->withHeaders([
                    $requestIdHeader => (string) Context::get('request_id'),
                ]);
            }

            return $next($request);
        });

        Queue::createPayloadUsing(function () {
            if (! Context::has('request_id')) {
                return [];
            }

            return [
                'context' => [
                    'request_id' => (string) Context::get('request_id'),
                ],
            ];
        });

        Event::listen(JobProcessing::class, function (JobProcessing $event) {
            Context::flush();

            $payloadContext = $event->job->payload()['context'] ?? [];

            if (is_array($payloadContext) && $payloadContext !== []) {
                Context::add($payloadContext);
            }
        });

        $flushContext = static function () {
            Context::flush();
        };

        Event::listen(JobProcessed::class, $flushContext);
        Event::listen(JobFailed::class, $flushContext);

        if ($this->app->runningInConsole() && Context::missing('request_id')) {
            Context::add([
                'request_id' => (string) Str::uuid(),
                'execution_context' => 'console',
            ]);
        }

        $sitterObserver = $this->app->make(SitterDelegationObserver::class);

        SitterDelegation::observe($sitterObserver);
        SitterAssignment::observe($sitterObserver);
    }
}
