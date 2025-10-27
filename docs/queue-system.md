# Queue and Scheduler Architecture

This document describes the new background processing system that moves the
project to Laravel's native queue and scheduler tooling.  The goal is to
standardise all background work, replace ad-hoc cron jobs, and provide a
single place to monitor long-running tasks.

## Queue driver

Laravel's queue configuration (`config/queue.php`) should be updated so that
the default connection uses either the database or Redis driver:

- **Database driver** – creates a `jobs` table for queued work and a
  `failed_jobs` table for failures.  Run `php artisan queue:table` followed by
  `php artisan migrate` to create the schema.
- **Redis driver** – requires the `redis` PHP extension and a running Redis
  instance.  Configure the connection in `config/database.php` and set
  `QUEUE_CONNECTION=redis` in the environment.

Both drivers are supported so environments without Redis can fall back to the
database queue while production can take advantage of Redis' performance.

## Job classes

Each background task is implemented as a dedicated job class stored in
`app/Jobs`.  Jobs encapsulate the work that needs to be executed, keep the
`handle()` method small, and expose any dependencies through constructor
injection.  Create a new class with `php artisan make:job ExampleJob` and move
existing logic from legacy scripts into the job's `handle()` method.

To dispatch work, call the job's static `dispatch()` helper from controllers,
commands, or services:

```php
ExampleJob::dispatch($payload)->onQueue('high');
```

Use queue names to separate critical work from low-priority tasks.

## Scheduled tasks

All recurring background work should be defined in `app/Console/Kernel.php`'s
`schedule()` method.  Replace crontab entries with scheduler definitions that
invoke queued jobs or artisan commands:

```php
protected function schedule(Schedule $schedule): void
{
    $schedule->job(new ProcessExpiredAuctionsJob())
        ->name('process-expired-auctions')
        ->everyTenMinutes();

    $schedule->command('reports:generate --type=daily')
        ->withoutOverlapping()
        ->dailyAt('01:00');
}
```

### Minute game tick

The Travian minute tick is coordinated by the `game:tick` artisan command
(`app/Console/Commands/GameTick.php`).  The scheduler dispatches this command
once per minute, and the command in turn queues the shard-aware jobs that power
resource production, build queue completion, movement resolution, oasis
respawns, and crop starvation enforcement.  Each job is pushed onto the
`automation` queue so the worker pool can process them independently without
blocking the scheduler thread.

Register any custom artisan commands under `app/Console/Kernel.php` so they are
discoverable by the scheduler.

## Worker management

Run the queue worker with `php artisan queue:work` (or
`php artisan queue:work redis --queue=high,default`).  In production, run these
workers under Supervisor so they auto-restart and can be scaled horizontally.
Use the Laravel Horizon dashboard when Redis is available for additional
monitoring.

## Failure handling

Configure the `failed` queue connection in `config/queue.php` so failed jobs are
logged to the database.  Implement the `failed()` method on job classes when the
application needs to react to permanent failures (for example, to alert on a
critical task).

## Environment variables

Add the following keys to `.env` to support the queue and scheduler:

```
QUEUE_CONNECTION=database
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

Update deployment documentation so that migrations run and the queue worker is
started after each release.
