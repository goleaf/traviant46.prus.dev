# Infrastructure Stack Overview

This document summarises the target production topology for the Laravel
migration.  It captures the baseline services that must be present in every
environment and highlights optional components that can be enabled to squeeze
additional performance from the stack.

## Nginx (existing)

Nginx remains the public-facing web server.  Reuse the existing server blocks
and continue to terminate TLS at Nginx.  Upstream requests should be forwarded
to PHP-FPM (or Octane, when enabled) via `fastcgi_pass` / `proxy_pass`.  Keep
the current log rotation policies and rate limiting rules that were already in
place for legacy Travian deployments.

## Supervisor-managed queue workers

Systemd unit files are no longer used for Laravel's queue workers.  Replace the
legacy `TravianTaskWorker.service` units with Supervisor programs so that queue
processes can be scaled horizontally and restarted automatically.  The
repository now ships a dedicated provisioning worker configuration at
`services/supervisor/provisioning-worker.conf`; it targets the provisioning
queue (`queue:work --queue=provisioning`), limits `max-time` to 900 seconds, and
runs a single process so the infrastructure API tasks complete deterministically
even under failures.  Drop the file into `/etc/supervisor/conf.d/` and reload
Supervisor to activate it.

The generic baseline below demonstrates the standard options for a default
queue worker.  Adjust the queue argument, process count, or timeout to align
with each workload.  For example, the repository also includes
`services/supervisor/automation-worker.conf`, which keeps the automation queue
processing the scheduled jobs dispatched from `app/Console/Kernel.php`.

```ini
[program:laravel-queue]
command=php /var/www/html/artisan queue:work --sleep=1 --tries=3 --max-time=3600
user=www-data
numprocs=2
process_name=%(program_name)s_%(process_num)02d
autostart=true
autorestart=true
stopwaitsecs=3600
stderr_logfile=/var/log/supervisor/laravel-queue.err.log
stdout_logfile=/var/log/supervisor/laravel-queue.out.log
```

Add additional programs for the scheduler (`php artisan schedule:work`) or any
high-priority queues as required.  Deploy the config to
`/etc/supervisor/conf.d/` and reload Supervisor with `supervisorctl reread` and
`supervisorctl update` during releases.

## Laravel Octane (optional)

Octane can be enabled on performance-sensitive game worlds.  Run it behind
Nginx with either the Swoole or RoadRunner driver.  Keep PHP-FPM as the default
execution model and add Octane to specific hosts by provisioning an extra
Supervisor program (e.g., `program:octane`) that executes
`php artisan octane:start --server=swoole --watch`.

## Redis 6.0+

Redis 6.0 or newer is required for cache, queue, and session backends.  Configure
Laravel's `cache`, `queue`, and `session` drivers to point at the Redis
instance via `.env` variables (`REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT`).
Enable persistence (AOF or RDB) per environment requirements and ensure Redis is
monitored for memory pressure.  Horizon can run on top of the same Redis
cluster for queue visibility.
