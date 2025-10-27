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
which one to use. When opting into Redis, make sure the following environment
variables are set so that the queue worker can reach the correct host:

```env
QUEUE_CONNECTION=redis
REDIS_HOST=redis  # or 127.0.0.1 when running locally without Docker
REDIS_PASSWORD=   # leave empty when Redis does not require authentication
REDIS_PORT=6379
```

The database driver is still a valid choice for single-node deployments. Just
remember to run the queue migration (`php artisan queue:table`) in every
environment before dispatching jobs so that the `jobs` table exists.

```env
QUEUE_CONNECTION=database   # fall back option
# QUEUE_CONNECTION=redis    # optional high-throughput driver
```

Regardless of the driver you choose, make sure the queue worker is running.
Laravel supports multiple worker strategies:

- **Foreground worker (local development):** `php artisan queue:work` or
  `php artisan queue:listen` while developing. You can pass `--queue=high,default`
  when you need to prioritise different queues.
- **Daemon worker (production):** Configure Supervisor, Docker health
  checks, or Laravel Horizon to ensure the worker process is always restarted on
  failure. A minimal Supervisor config looks like this:

  ```ini
  [program:laravel-queue]
  command=php /var/www/html/artisan queue:work --sleep=1 --tries=3 --max-time=3600
  user=www-data
  numprocs=2
  process_name=%(program_name)s_%(process_num)02d
  autostart=true
  autorestart=true
  stopwaitsecs=3600
  ```

Monitor the worker logs (`storage/logs/laravel.log` by default) or Horizon's UI
so that failed jobs can be retried promptly.

## Scheduled tasks

All recurring work is registered inside `app/Console/Kernel.php`. The
`schedule()` method now dispatches the relevant job classes on the cadence
required by the game server:

- Resource balancing jobs run every minute.
- Notification and messaging jobs run every five minutes.
- Costly maintenance routines (statistics, pruning, reports) run hourly or
  nightly as appropriate.

Run the scheduler locally with `php artisan schedule:run` (or
`php artisan schedule:test "* * * * *"` when you want to confirm the cadence)
or set up a system cron entry such as:

```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Job classes

Each background task has been migrated to its own Laravel job class located in
`app/Jobs`. Every job encapsulates a single unit of work and can be dispatched
from controllers, command bus handlers, or the scheduler. Jobs implement the
`handle()` method which wraps the previous background scripts and ensures they
can be retried safely. When creating new jobs, follow these conventions:

- Keep the constructor focused on gathering the data needed to process the job;
  do not execute queries or long-running logic inside `__construct`.
- Explicitly define the queue name via the `$queue` property when the job should
  target a non-default queue (for example, `$queue = 'high'`).
- Apply middleware such as `WithoutOverlapping`, `RateLimited`, or
  `ThrottlesExceptions` to protect downstream services.
- Set `$timeout` or `$tries` on long-running jobs to avoid worker starvation.

For long-running scripts, consider adding rate limiting and explicit timeout
configuration via job middleware. This keeps the queue responsive and prevents a
single failure from blocking subsequent work. If a job depends on external
services, wrap the calls in retryable HTTP/database clients so that transient
failures do not cause a cascade of retries.

### Handling failures

The queue system stores failed jobs in the `failed_jobs` table when you run the
`php artisan queue:failed-table` migration. Use `php artisan queue:retry` or
`php artisan queue:forget` to manage individual failures. For production,
configure alerts (via Horizon, Sentry, Bugsnag, etc.) to notify the team when a
job fails repeatedly.

## Local development checklist

1. Run database migrations to ensure the `jobs` table exists: `php artisan
   queue:table && php artisan queue:failed-table && php artisan migrate`.
2. Seed test jobs with `php artisan tinker` and `dispatch(new ExampleJob())` to
   verify the queue wiring.
3. Start the queue worker: `php artisan queue:work --tries=1 --timeout=90`
   (use `--queue` if you split work across multiple queues).
4. Run the scheduler once to verify job registration: `php artisan
   schedule:run`.
5. Monitor `storage/logs/laravel.log` or Horizon's dashboard for job output
   during development.

Following these steps will keep the new queue-based background system operating
reliably in every environment.

### CropStarvationJob

`CropStarvationJob` scans the `villages` table for entries whose crop balance is
negative and whose stored granary empty ETA has elapsed. When it finds an
eligible village the job triggers `ApplyStarvationAction` to deduct the starving
troops and immediately notifies both the village owner and watcher via a queued
`VillageStarvationNotification`. The job runs on the `automation` queue and is
dispatched every game tick through the `game:tick` console command.
