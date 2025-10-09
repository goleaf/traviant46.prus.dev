# Background Job & Scheduler System

This project now relies on Laravel's queue and scheduling facilities for all
background workloads. The following sections summarise the key pieces you need
for local development and production deployments.

## Queue driver configuration

Laravel's `queue` service is configured to use the database driver by default
so that every queued job is persisted in the `jobs` table. For environments that
have Redis available, you can switch to the Redis driver by updating the
`QUEUE_CONNECTION` environment variable in your `.env` file. The configuration in
`config/queue.php` already defines both connections, so you only need to select
which one to use.

```env
QUEUE_CONNECTION=database   # fall back option
# QUEUE_CONNECTION=redis    # optional high-throughput driver
```

Regardless of the driver you choose, make sure the queue worker is running.
This can be managed by Supervisor, systemd, Docker, or Laravel Horizon depending
on the hosting environment.

## Scheduled tasks

All recurring work is registered inside `app/Console/Kernel.php`. The
`schedule()` method now dispatches the relevant job classes on the cadence
required by the game server:

- Resource balancing jobs run every minute.
- Notification and messaging jobs run every five minutes.
- Costly maintenance routines (statistics, pruning, reports) run hourly or
  nightly as appropriate.

Run the scheduler locally with `php artisan schedule:run` or set up a system
cron entry such as:

```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Job classes

Each background task has been migrated to its own Laravel job class located in
`app/Jobs`. Every job encapsulates a single unit of work and can be dispatched
from controllers, command bus handlers, or the scheduler. Jobs implement the
`handle()` method which wraps the previous background scripts and ensures they
can be retried safely.

For long-running scripts, consider adding rate limiting and explicit timeout
configuration via job middleware. This keeps the queue responsive and prevents a
single failure from blocking subsequent work.

## Local development checklist

1. Run database migrations to ensure the `jobs` table exists: `php artisan
   queue:table && php artisan migrate`.
2. Start the queue worker: `php artisan queue:work` (use `--queue` if you split
   work across multiple queues).
3. Run the scheduler once to verify job registration: `php artisan
   schedule:run`.
4. Monitor `storage/logs/laravel.log` for job output during development.

Following these steps will keep the new queue-based background system operating
reliably in every environment.
