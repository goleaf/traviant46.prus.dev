# Backend Stack Overview

This document describes the technology stack that underpins the modern Laravel
backend for TravianT.  It supplements the broader migration plan documented in
[`travian-to.plan.md`](../travian-to.plan.md) with actionable guidance on how the
selected components should be configured and used together.

## Application Core

- **Laravel 12 + PHP 8.2/8.3** – the base framework for HTTP routing, console
  tooling, configuration, and dependency injection.  Keep the framework updated
  via Composer to receive security fixes.
- **Eloquent ORM** – provides typed models for each table in the redesigned
  schema.  Use factories and seeders for testing and favour query scopes over
  raw SQL for reusable filters.

### Database Practices

- Enable strict mode in `config/database.php`.
- Use database migrations for every schema change.
- Prefer UUIDs or ULIDs for external identifiers while keeping numeric primary
  keys for internal relations.
- Mirror existing Travian worlds through a multi-database configuration when
  necessary (e.g., `mysql_world1`, `mysql_world2`).

## Authentication & Authorisation

- **Laravel Fortify** supplies the authentication pipeline (login, registration,
  password reset, email verification).  Customise the Fortify actions to cover
  sitter logins, maintenance bypasses, and other Travian-specific behaviours.
- **Laravel Sanctum** issues personal access tokens for third-party consumers
  (game tools, admin dashboards).  Guard APIs with Sanctum middleware and scope
  tokens so that privilege escalations are prevented.
- **Spatie laravel-permission** handles role/permission assignment.  Model
  alliance, sitter, and staff roles with dedicated permission groups.  Expose a
  policy layer so controllers and jobs authorise sensitive operations.

## Background Processing

- **Laravel Queue with Redis driver** – queue connection defined in
  `config/queue.php` using the `redis` driver.  Dispatch time-critical work to a
  `high` queue while long-running or best-effort tasks run on `default` or
  `low`.
- **Jobs & Listeners** – store jobs in `app/Jobs` and event listeners in
  `app/Listeners`.  Keep job handlers idempotent; use Redis locks or database
  uniqueness constraints to avoid duplicate execution.
- **Laravel Horizon** – deploy Horizon for monitoring queue throughput,
  processing times, and failures.  Configure supervisors per workload class and
  expose the Horizon dashboard behind staff authentication.

## Activity & Auditing

- **Spatie activitylog** – record critical player and staff actions (e.g.,
  resource grants, alliance management, ban decisions).  Store metadata such as
  acting world, IP address, and impersonation context.
- Retain audit trails for at least 90 days in a dedicated database table and
  archive older entries to cold storage.

## Operational Concerns

- Configure Supervisor (or systemd) to run `php artisan horizon` for queue
  processing and `php artisan schedule:work` for cron replacement.
- Centralise sensitive settings in `.env` and provide production-ready examples
  in `.env.example`.
- Instrument the application with Laravel Telescope or OpenTelemetry exporters
  when operating at scale.
- Document recovery procedures for queue outages: restart Horizon, clear
  stuck jobs via `php artisan horizon:terminate`, and inspect Redis visibility
  timeouts.

## Next Steps

1. Scaffold the Laravel application by following the migration roadmap outlined in [`travian-to.plan.md`](../travian-to.plan.md).
2. Install the required packages via Composer:
   ```bash
   composer require laravel/fortify laravel/sanctum laravel/horizon \
       spatie/laravel-permission spatie/laravel-activitylog
   ```
3. Publish package configuration files and adjust them to match Travian’s
   multi-world requirements.
4. Build seed data for development environments to validate the new stack.

Refer to `docs/queue-system.md` for background job architecture details and review [`README.md`](README.md) together with [`legacy-controller-mapping.md`](legacy-controller-mapping.md) for Fortify configuration and controller integration guidance.
