# Modernization Assessment: Custom MVC to Laravel 11

## Current System Overview
- **Runtime**: PHP 7.3–7.4 with a custom MVC framework, bespoke autoloader, and mysqli-based database wrapper.
- **Application Scope**: ~45 models, 80+ controllers, and a template-based view layer supporting Persian, Arabic, English, and Greek localizations.
- **Infrastructure**: Redis-backed caching and sessions, custom job system leveraging `pcntl_fork`, and systemd-managed automation services for game mechanics (buildings, troops, battles, artifacts, heroes, alliances, etc.).
- **Data Layer**: Approximately 70+ database tables inferred from the schema dump.

## Target Platform Goals
- Upgrade to **Laravel 11** on **PHP 8.2+**.
- Replace the custom stack with **Eloquent ORM**, **Blade** components, **Laravel Breeze/Fortify** authentication, and **Livewire 3 + Flux UI** for interactive interfaces.
- Adopt Laravel's **queue system** (database or Redis driver) to supersede the `pcntl_fork` job framework.
- Transition toward a **service-oriented architecture** with modular domain services.

## Migration Considerations
1. **Schema Redesign**
   - Map current tables and relationships into Eloquent models, normalizing where necessary.
   - Introduce migrations and seeders to codify schema evolution.
2. **Domain Logic Extraction**
   - Refactor complex mechanics (e.g., battles, hero progression, alliance interactions) into dedicated service classes with clear contracts.
   - Identify reusable domain events for Laravel's event bus.
3. **Presentation Layer**
   - Rebuild templates as Blade views/components; leverage localization files for multilingual support.
   - Use Livewire + Flux UI for real-time dashboards (e.g., troop movements, battle reports).
4. **Job Processing**
   - Migrate systemd-triggered automation into Laravel console commands and queued jobs.
   - Evaluate Horizon for queue monitoring when using Redis.
5. **Caching & Sessions**
   - Replace custom Redis integration with Laravel's cache/session drivers, centralizing configuration via `.env`.
6. **Testing & Tooling**
   - Establish PHPUnit/Pest coverage for critical mechanics.
   - Integrate static analysis (Psalm/PHPStan) to maintain code quality during refactors.

## Recommended Phased Approach
1. **Discovery & Documentation**
   - Inventory controllers, models, templates, and SQL queries.
   - Produce sequence diagrams for automation workflows and battle resolution logic.
2. **Infrastructure Setup**
   - Provision a Laravel 11 baseline project with Dockerized PHP 8.2, Redis, and MySQL services.
   - Implement authentication scaffolding (Breeze/Fortify) and configure localization.
3. **Data Migration**
   - Design Eloquent models and migrations that mirror the legacy schema.
   - Create ETL scripts to migrate live data, including localization content.
4. **Feature Parity Implementation**
   - Port domain services incrementally, validating each with automated tests and QA.
   - Develop Livewire dashboards and Blade views replicating key user flows.
5. **Automation & Background Processing**
   - Recreate cron/systemd jobs as Laravel scheduled tasks and queue workers.
   - Deploy Horizon (if using Redis queues) for monitoring and scaling.
6. **Cutover & Stabilization**
   - Run both systems in parallel, syncing data via database replication or API bridges.
   - Conduct load testing, security audits, and final user acceptance before decommissioning the legacy stack.

## Risks & Mitigations
- **Complex Domain Rules**: Capture business rules via domain-driven documentation and unit tests prior to refactor.
- **Localization Regression**: Centralize translations in Laravel's lang files; automate regression testing for multi-language templates.
- **Downtime During Migration**: Employ blue/green deployments and database migrations with backward-compatible changes.

## Next Steps
- Schedule stakeholder workshops to validate requirements and prioritize features.
- Allocate resources for schema reverse-engineering and automated test creation.
- Draft a project timeline that accommodates phased rollout and parallel run periods.
