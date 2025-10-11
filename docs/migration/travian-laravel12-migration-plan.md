# Travian T4.6 → Laravel 12 Complete Migration Plan

## 0. Executive Overview
- **Objective:** Replace the legacy TravianT4.6 PHP 7.3–7.4 codebase with a unified Laravel 12 application that combines the existing `/backend` Fortify-powered authentication stack with the full set of Travian game mechanics.
- **Modern Stack:** PHP 8.3, Laravel 12, Livewire 3, Vite, TailwindCSS/Flux UI, Redis, MariaDB 10.6+/MySQL 8.0+.
- **Key Drivers:** Security, maintainability, horizontal scalability, Livewire-driven reactive UI, and reuse of the proven `/backend` Laravel components (multi-account detection, sitter system, Fortify guards).
- **Migration Strategy:** Iterative “strangler” approach with schema redesign, dual-running critical services, automated regression suites, and staged cutovers.

---

## 1. Current Assets & Gap Analysis

### 1.1 Legacy Travian Stack
- Custom MVC framework with bespoke autoloader (`main_script/include/Core/Autoloader.php`), mysqli database wrapper, and hand-written routing/controllers.
- ~90 tables with non-normalized naming, INT timestamps, and sparse indexes (`main.sql`).
- ~45 models, 80+ controllers, and 200+ templates distributed across `main_script/include`.
- Custom job system using `pcntl_fork`, Redis-backed session layer, handcrafted localization files in `resources/Translation/`.

### 1.2 Existing Laravel `/backend`
- Laravel-based auth, sitter, and anti multi-account features located under `/backend` with Fortify integration and Redis queue workers.
- Eight Laravel migrations defining modernized tables for the sitter/multi-account subsystems.
- Seven Eloquent models already aligned with Laravel conventions.

### 1.3 Critical Gaps
- PHP version gap (7.3 → 8.3) requiring code rewrite to remove deprecated constructs (e.g., `each()`, curly brace string offsets).
- Inconsistent database schema lacking foreign keys, timestamps, and descriptive column names (e.g., `f1–f40`, `u1–u10`).
- UI stack mismatch: legacy templates vs. target Livewire/Flux component model.
- Job orchestration differences (pcntl vs. Laravel Queues/Horizon).

---

## 2. Guiding Principles
1. **Laravel-First:** All new features and rewrites live inside `/app` using Laravel conventions. Legacy code is only read for parity.
2. **Livewire-Only UI:** Screens are rebuilt as Livewire components; Blade views act as shells that compose Livewire and Flux UI primitives.
3. **Database Normalization:** Adopt Laravel-friendly schemas (snake_case, unsigned bigints, timestamps, foreign keys) while providing views/ETL scripts for legacy data.
4. **Incremental Cutovers:** Maintain player continuity by syncing critical tables during transition and double-writing where required.
5. **Automation:** Comprehensive test coverage (PHPUnit, Pest), database seeders for scenarios, Dusk component tests for Livewire flows.

---

## 3. Target Architecture Snapshot
- **Application:** Monolithic Laravel 12 app in project root; `/backend` code merged under `app/` and `database/` namespaces.
- **Presentation:** Livewire 3 with Flux UI; Vite handles asset bundling; Tailwind configured via PostCSS.
- **Domain Layer:** Feature-focused modules grouped by bounded contexts (Accounts, Villages, Map, Alliance, Combat, Economy, Communication).
- **Persistence:** Eloquent models per bounded context backed by modular migrations; Redis for cache/sessions/queues; Horizon for monitoring.
- **Integrations:** Mail notifications via Laravel mailables, GeoIP via service provider abstraction, external payment hooks using Sanctum.

---

## 4. Phase Plan & Timeline

### Phase 0 (Week 0): Project Bootstrapping
- Upgrade tooling: ensure PHP 8.3 runtime, Composer 2.7, Node 20.
- Initialize new Laravel 12 skeleton in repository root (or upgrade existing Laravel install).
- Configure `.env` to point to cloned legacy database copy; set Redis queue/cache/session drivers.
- Introduce CI pipelines (GitHub Actions) running `composer test`, `npm run build`, and static analyzers (Larastan, Pint).

### Phase 1 (Weeks 1–3): Core Framework Merge
1. **Backend Merge:** Relocate `/backend/app`, `/backend/config`, and `/backend/database` resources into main Laravel tree, resolving namespace collisions and harmonizing service providers.
2. **Auth & Security:** Enable Fortify, Sanctum, sitter features, and multi-account detection in the unified app; wire Redis session handling.
3. **Base Livewire Layout:** Create shared layout, nav, and notification Livewire components leveraging Flux UI.
4. **Localization Pipeline:** Import legacy translation strings into Laravel localization files and create translation loader for JSON/array hybrids.

### Phase 2 (Weeks 3–7): Database Redesign & Data Access Layer
1. **Schema Modeling:** Produce ERDs and migration files for all bounded contexts (Accounts, Village, Alliance, Combat, Map, Communication, Economy, Hero, Artifacts, Quests, Admin Logs).
2. **Migration Strategy:**
   - Create new normalized tables (e.g., `villages`, `village_buildings`, `village_units`, `alliances`, `alliance_memberships`, `movements`, `combat_reports`).
   - Provide compatibility views or staging tables to map legacy column names (e.g., `f1` → `building_slots[1]`).
   - Implement data migration scripts (Laravel commands) that pull from legacy schema and insert into new tables using chunked processing.
3. **Model Layer:** Define Eloquent models, relationships, and factories; set up policies for authorization rules.
4. **Caching Strategy:** Port Redis caching logic into dedicated cache repositories using tagged caches and cache invalidation events.

### Phase 3 (Weeks 6–11): Feature Parity Implementation
Break down by bounded context, develop Livewire UIs, services, and tests.

- **Accounts & Profile:** Registration, activation, sitter management, account settings, notifications, gold/silver balance, multi-account alerts.
- **Village Management:** Village overview, building queues, resource production, construction/destruction, smithy/blacksmith upgrades.
- **Troop & Combat:** Training, movement planner, rally point UI, battle simulation, hero adventures, artifacts handling.
- **Alliance & Communication:** Alliance dashboards, diplomacy, forums, messages, reports, notes, alliance bonuses.
- **Economy:** Marketplace trades, auctions, traderoutes, raidlists, farmlists, resource balancing.
- **World/Map:** Interactive Livewire map components (chunked loading, fog-of-war), map markers, surrounding tiles, block management.

Each feature increment includes:
- Livewire component(s) + Blade shells
- Application services/domain actions
- Jobs/listeners for asynchronous flows
- Pest/PHPUnit tests + Livewire component tests

### Phase 4 (Weeks 10–13): Background Processing & Integrations
- Replace legacy `pcntl_fork` jobs with Laravel Queues (Redis driver) and scheduled commands.
- Implement Horizon dashboard and metrics (queue wait times, throughput).
- Port mail notification scripts to Laravel mailables and notifications.
- Integrate payment gateways and gold shop flows using Sanctum APIs.
- Configure WebSockets (Laravel Reverb or Pusher) for real-time events (incoming attacks, alliance chat).

### Phase 5 (Weeks 12–15): Parallel Run & Data Cutover
1. **Dual-Write Layer:** During beta, write critical changes to both legacy and Laravel tables using database triggers or Laravel events hooked into legacy endpoints.
2. **Sync Jobs:** Nightly Artisan commands to reconcile divergence (e.g., raid reports, map markers).
3. **User Beta:** Invite limited players, gather telemetry, monitor error logs, adjust balancing scripts.
4. **Performance Testing:** Load test with k6/Artillery, profile slow queries via Telescope/Horizon.

### Phase 6 (Week 16): Final Cutover & Decommission
- Freeze legacy app to read-only, run final data sync.
- Switch DNS / load balancer to Laravel 12 app.
- Monitor metrics, queue health, and error reporting.
- Archive legacy codebase, retire cron jobs and pcntl workers, update documentation.

---

## 5. Risk Mitigation
- **Data Integrity:** Extensive migration rehearsals on anonymized data; checksum comparisons before cutover.
- **Downtime:** Blue/green deployment strategy with rollback playbook.
- **Performance:** Proactive indexing, Horizon autoscaling plans, Octane evaluation.
- **Security:** Fortify hardening, rate limiting, audit logging with Spatie Activity Log.

---

## 6. Tooling & Automation Checklist
- Laravel Pint, PHPStan/Larastan level 6, Psalm optional.
- Rector rulesets for upgrading legacy PHP constructs to modern syntax.
- PHPUnit/Pest suites covering domain logic, Livewire component tests, Dusk for end-to-end flows.
- GitHub Actions workflows for CI; Envoy/Ansible scripts for deployments.

---

## 7. Deliverables & Documentation
- Updated architecture decision records (ADRs) for key choices (schema redesign, Livewire architecture, queue strategy).
- Runbooks for operations (deployment, scaling queues, cache flush procedures).
- Developer handbook describing module boundaries, coding standards, and onboarding steps.

---

## 8. Success Metrics
- 100% of active gameplay features available via Livewire in Laravel 12.
- <200ms average server response time under load test baseline.
- Zero data loss during migration (validated by automated reconciliation scripts).
- Player retention equal or better than legacy baseline during parallel run.
- Operational dashboard visibility (Horizon, Telescope, Prometheus) for all queues and critical KPIs.

