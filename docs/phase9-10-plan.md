# Phase 9-10 Migration & Deployment Plan

## Overview
This document outlines the actionable tasks required to complete the Phase 9 and Phase 10 objectives for migrating the legacy admin tooling into Laravel and preparing the new stack for production launch.

## 9.1 Admin Interface Migration

### Target Livewire Components
Create the following Livewire components under `app/Livewire/Admin` with matching Blade templates in `resources/views/livewire/admin`:

1. `Dashboard`
2. `PlayerManagement`
3. `VillageEditor`
4. `GiftSystem`
5. `PunishmentSystem`
6. `ServerSettings`

For each component:
- **Route registration**: add guarded routes inside `routes/admin.php` (new file) and load them via the RouteServiceProvider. Restrict access using the existing `can:admin` middleware.
- **Authorization**: reuse existing policies or create dedicated policy classes to mirror legacy permission checks.
- **Data bindings**: wire component state to the corresponding Eloquent models (e.g., `User`, `Village`, `ServerSetting`). Ensure method names remain parallel to the legacy PHP actions for easier verification.
- **UI parity**: port the legacy Blade snippets from `main_script/include/admin/` into reusable components. Use a base layout `resources/views/layouts/admin.blade.php` with shared navigation and flash messaging.
- **Form validation**: leverage Livewire form objects or standard `$this->validate()` calls to match server-side validation previously performed in the legacy scripts.
- **Auditing**: dispatch domain events (e.g., `AdminActionLogged`) after every state-changing operation to feed the custom logging pipeline described below.

### Data Access Considerations
- Map the legacy data fetchers to dedicated repository classes (`app/Repositories/Admin`). This ensures complex joins (e.g., sitter relationships, punishment history) remain encapsulated.
- Replace ad-hoc SQL queries with query builder equivalents while preserving identical filters and sorting.
- Integrate caching (Redis tags) for expensive lookups (top players, online status) with invalidation hooks triggered from model events.

## 9.2 Monitoring & Logs

### Laravel Telescope
- Require Telescope in the composer manifest and enable it in non-production environments.
- Configure a custom gate that only grants access to admin accounts.
- Ship a production configuration that stores Telescope entries in Redis with a daily prune command.

### Custom Game Event Logging
- Implement a dedicated `GameEventLogger` service that writes structured logs (JSON) to `storage/logs/game-events.log` and optionally forwards them to Logstash via UDP.
- Standardize payload contracts by introducing `App\Logging\Events\*` value objects representing player actions, punishments, and rewards.
- Hook into Eloquent model observers, queue jobs, and Livewire actions to emit these events.

### Performance Monitoring & Error Tracking
- Instrument key service classes with Laravel's `Event::listen` and `Clockwork` (if adopted) to capture execution time metrics.
- Provide stubs for Sentry integration that can be toggled via environment variables (`SENTRY_DSN`, `SENTRY_ENV`).
- Document alert thresholds for queue latency, failed jobs, and request error rates.

## 10.1 Performance Optimization

1. **Database Query Optimization**: Run `php artisan tinker` driven spot checks for heavy queries, add missing indexes via new migration files, and document each optimization in `docs/performance-notes.md`.
2. **N+1 Prevention**: Enable Laravel Debugbar/Telescope query watcher in staging; enforce eager loading in repositories.
3. **Redis Caching Strategy**: Draft cache keys namespaced by game world (`world:{id}:resource`). Ensure cache invalidation on model updates and consider TTL alignment with resource tick intervals.
4. **Queue Worker Scaling**: Define desired concurrency per queue in `config/queue.php` and add Supervisor program entries with auto-restart policies.
5. **Horizon Setup**: Install Laravel Horizon, lock dashboard behind admin middleware, and configure notification channels for failed job spikes.

## 10.2 Deployment Preparation

- **Nginx**: Provide updated server block templates that route `/admin` to the Laravel app and proxy legacy static assets as needed.
- **Supervisor**: Create process definitions for `queue:work`, `schedule:run`, Telescope pruning, and (optionally) Octane.
- **Laravel Octane (Optional)**: Evaluate using Swoole mode in staging; document required system packages and fallbacks if incompatibilities arise.
- **Nginx**: Verify existing server blocks proxy to Octane or PHP-FPM appropriately after Supervisor-managed processes are in place.

## 10.3 Cutover Plan

1. Announce a maintenance window to players through in-game messaging and community channels.
2. Halt the old automation engine (TaskWorker) after processing the current job queue.
3. Take a final read-only snapshot of the production database and import any delta data into Laravel.
4. Update Nginx upstreams to point to the Laravel application servers.
5. Start Laravel queue workers (Supervisor + Horizon) and verify they consume jobs correctly.
6. Monitor application health, job processing, and error logs for 24-48 hours using Telescope/Horizon dashboards and external monitoring.
7. Keep the legacy system in a hot-standby mode for at least one week, with rollback instructions documented in `docs/rollback-plan.md`.

## Next Steps
- Assign component owners and timeline estimates for each deliverable.
- Establish a dedicated staging environment mirroring production topology.
- Schedule regular check-ins (twice per week) to track progress and unblock dependencies.
