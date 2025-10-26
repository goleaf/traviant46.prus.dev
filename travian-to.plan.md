# Travian T4.6 → Laravel 12 Complete Migration Plan
## ULTRA-DETAILED IMPLEMENTATION GUIDE

---

## Project Analysis Summary

### Current State (Detailed Inventory)

**Architecture:**
- Custom MVC framework (non-Laravel, non-standard)
- PHP 7.3-7.4 requirement (needs upgrade to 8.2+)
- Custom autoloader (`Core\Autoloader.php`) with namespace-to-file mapping
- Custom database wrapper (`Core\Database\DB.php`) using mysqli
- Custom session handler with Redis backend
- Custom routing via `Controller\*Ctrl.php` pattern

**Database Schema (90 tables total):**
1. **User/Auth Tables (6):** users, activation, login_handshake, activation_progress, deleting, newproc
2. **Village Tables (11):** vdata, fdata, wdata, available_villages, odata, building_upgrade, demolition, smithy, tdata, research, traderoutes
3. **Alliance Tables (16):** alidata, alistats, allimedal, ali_invite, ali_log, diplomacy, alliance_notification, alliance_bonus_upgrade_queue, forum_forums, forum_edit, forum_options, forum_post, forum_topic, forum_vote, forum_open_players, forum_open_alliances
4. **Movement/Combat Tables (8):** movement, a2b (attacks-to-be), enforcement, trapped, units, send
5. **Hero Tables (6):** hero, face, items, inventory, adventure, accounting
    6. **Artifact Tables (3):** artefacts, artlog
7. **Communication Tables (4):** mdata (messages), ndata (reports), messages_report, notes
8. **Marketplace Tables (3):** market, auction, bids, raidlist
9. **Map Tables (7):** map_block, map_mark, mapflag, marks, blocks, surrounding
10. **Quest/Medal Tables (3):** daily_quest, medal, allimedal
11. **Farmlist Tables (3):** farmlist, raidlist, farmlist_last_reports
12. **Admin/Log Tables (9):** general_log, admin_log, log_ip, transfer_gold_log, ban_history, banQueue, multiaccount_log, multiaccount_users
13. **Game Config Tables (4):** config, summary, casualties, autoExtend
14. **Misc Tables (11):** links, infobox, infobox_read, infobox_delete, ignoreList, friendlist, changeEmail, notificationQueue, voting_reward_queue, buyGoldMessages, player_references

**Identified Issues with Current Schema:**
- Generic column names (`u1-u10` for units, `f1-f40` for buildings)
- No proper foreign keys
- Mixed naming conventions (snake_case, camelCase)
- Timestamps stored as INT(10) instead of TIMESTAMP
- No `created_at`/`updated_at` audit fields
- Large TEXT/LONGTEXT fields without optimization

**Code Structure (Detailed):**
- **45 Model Files** in `main_script/include/Model/`
- **80+ Controller Files** in `main_script/include/Controller/` (including 60+ AJAX endpoints)
- **200+ Template Files** in `main_script/include/resources/Templates/`
- **30+ Service/Helper Files** in `main_script/include/Game/`
- **Custom Job System** with pcntl_fork spawning worker processes
- **Multi-language System** with translation files in `resources/Translation/`

**Dependencies:**
- PHP Redis extension (required)
- PHP GeoIP extension
- MariaDB/MySQL database
- Process supervision: systemd for web/mail services, Supervisor for Laravel queue workers
- Legacy automation scripts triggered via cron on two dedicated utility hosts
- External analytics feed pushing hourly CSV exports to a vendor-managed S3 bucket
- Mail delivery handled through Postmark API with custom bounce webhooks routed to `/services/mailNotify/`
- Payment gateway integration through XSolla SDK embedded in `_travian/services/Payment/`
- Realtime chat relayed via long-polling endpoints backed by Redis pub/sub
- Monitoring stack composed of Zabbix (infrastructure) and custom PHP log aggregators

### Operational Snapshot & Metrics

- **Active Worlds:** 6 concurrently running game worlds with staggered start dates; each world averages 12,000 daily active users (DAU).
- **Peak Load:** 2,400 concurrent actions per minute during tournament finale weeks; custom automation keeps background jobs under a 5-second completion SLA.
- **Data Footprint:** ~480 GB combined MySQL dataset with 1.2 TB of historical logs compressed in cold storage; nightly incremental backups stored in R2-compatible object storage.
- **Support Volume:** Community support team resolves ~150 tickets per day related to sitter delegation and alliance disputes; tooling depends on accurate audit trails and admin dashboards.
- **Release Cadence:** Legacy stack deploys monthly via rsync scripts; target cadence post-migration is bi-weekly with GitHub Actions/ArgoCD pipelines.
- **Compliance Requirements:** GDPR data export/delete workflows implemented manually—must be formalized through Laravel console commands with auditable logging.

### Target State (Detailed Requirements)

**Framework:**
- Laravel 12.x (latest stable release)
- PHP 8.2+ (ideally 8.3 for performance)
- Composer 2.x for dependency management

**Frontend Stack:**
- Livewire 3.5+ for reactivity (match `livewire/livewire` ^3.5)
- Flux UI library for components (match `livewire/flux` ^2.5)
- Alpine.js (bundled with Livewire)
- Vite 6 configuration aligned with Laravel 12 defaults
- Tailwind CSS (if Flux UI uses it)

**Backend Stack:**
- Eloquent ORM for database
- Laravel Queue (Redis driver)
- Laravel Fortify ^1.31 for authentication (per `backend/composer.json`)
- Laravel Sanctum for API tokens (if needed)
- Laravel Horizon 5.x for queue monitoring (Laravel 12 compatible)
- Spatie packages for permissions/activity log (if needed)

**Database:**
- MariaDB 10.6+ or MySQL 8.0+
- Redesigned schema following Laravel conventions
- Proper foreign key constraints
- Indexed columns for performance
- JSON columns for flexible data
- Timestamp columns should default to Laravel 12 precise timestamps (`$table->timestampsTz(6)`) with `useCurrentOnUpdate()` where applicable
- Prefer Laravel 12 native enum casting via `->enum()` or `AsEnumArrayObject` casts for stateful columns

**Infrastructure:**
- Kubernetes ingress controller managed by platform team
- Supervisor for queue workers (replace SystemD services)
- Laravel Octane (optional, for performance boost)
- Redis 6.0+ for cache/queue/sessions

---

## Phase 1: Foundation & Setup (Week 1-2)

### 1.0 Repository Bootstrap & Directory Alignment

- Create new Laravel 12 skeleton inside `/www/wwwroot/traviant46.prus.dev/backend` using `laravel new` to match `backend/` expectations.
- Introduce `_travian/` module directory under `backend/app/_travian` for legacy interop (aligns with existing repository reorganization).
- Update root composer workspace (`composer.json`) autoloading to map `_travian` services/models (already reflected in `backend/composer.json`).
- Relocate shared assets to `resources/` and publish the Laravel 12 public webroot to `resources/web/public` per migration plan.
- Ensure `bootstrap/app.php` adopts Laravel 12 closure-based configuration and registers legacy providers via `withProviders()`.
- Wire the new HTTP kernel layout (`app/Http/Kernel.php` with `middlewareGroups` constants) to reflect Laravel 12 defaults before custom middleware.

### 1.1 Laravel Installation

- Install Laravel 12 in new directory: `/www/wwwroot/traviant46.prus.dev/laravel-app` (or reuse `/backend` skeleton above if already initialized).
- Configure `.env` with existing database credentials and Redis endpoints.
- Install dependencies pinned in `backend/composer.json`: Livewire 3.5, Flux UI 2.5, Laravel Fortify ^1.31, Laravel Horizon 5.x.
- Set up multi-language support (laravel-translatable or similar).
- Configure Redis for cache/sessions/queues.
- Adopt Laravel 12 Vite presets (`resources/js/app.js`, `resources/css/app.css`) and ensure SSR/Vite config matches new directory layout.

### 1.2 Database Analysis & Schema Design

- Analyze existing `main_script/include/schema/T4.4.sql` (all tables)
- Map current schema to Laravel conventions:
- Snake_case column names
- `id` as primary key (BIGINT UNSIGNED)
- `created_at`, `updated_at` timestamps where applicable
- Proper foreign key relationships
- Pivot tables for many-to-many relationships
- **Critical Tables to Redesign:**
- `users` → add Laravel auth columns (email_verified_at, remember_token)
- `activation` → merge into users or separate email_verifications table
- `a2b` (attacks) → rename to `attacks`, better column names
- `villages` (wref/vref) → readable column names
- `fdata` (buildings) → `village_buildings`
- `units` → `village_units`, `movement_units`
- `ali_*` tables → `alliances`, `alliance_*` pattern
- All game mechanics tables need proper relationships

#### User/Auth Tables Detailed Redesign

**Goals:** Align all authentication-related tables with Laravel's guard/session expectations, preserve legacy data, and prepare for Fortify- and Sanctum-based flows.

**Shared Design Decisions:**
- Use `bigIncrements`/`unsignedBigInteger` for primary and foreign keys.
- Introduce `created_at`/`updated_at` everywhere (fall back to manual timestamps where the game stores UNIX times that must be preserved).
- Replace integer flag columns with expressive booleans/enums when migrating Eloquent models, while keeping compatibility columns for backfill scripts.
- Add cascading foreign keys referencing `users.id` to replace manual cleanup jobs.

**Table-by-Table Plan:**

- **`users`**
  - *Purpose:* Primary player accounts, sitter relations, alliance membership metadata.
  - *Issues:* Dozens of legacy INT columns storing timestamps, varchar CSV preferences, weak password storage (`VARCHAR(40)` for SHA1), no audit trail.
  - *Redesign:*
    - Core auth columns: `email`, `password`, `remember_token`, `email_verified_at`, `two_factor_secret` (Fortify), `banned_at`, `last_login_at` (as TIMESTAMPs).
    - Move alliance- and sitter-specific data into dedicated tables (`alliance_user`, `user_sitters`) to normalize later; keep read-only compatibility columns temporarily.
    - Introduce JSON columns for UI preferences (`display_preferences`, `report_filters`).
    - Store gold/silver balances as unsigned integers with descriptive names (`gold_balance`, `silver_balance`).
    - Migration strategy: create a new Laravel-aligned `users` table, import legacy data via migration script, keep raw columns in shadow tables for phased deprecation.

- **`activation`**
  - *Purpose:* Tracks activation codes and timers for new registrations.
  - *Issues:* Duplicates email verification info stored in `activation_progress`; stores raw codes without expiry index.
  - *Redesign:*
    - Merge into `email_verifications` table with columns `user_id`, `token`, `expires_at`, `completed_at`, `ip_address`.
    - Enforce unique constraint on `user_id` and `token`.
    - Backfill existing rows by mapping `activation.code` → `token` and computing expiry from current UNIX timestamp fields.
    - Decommission original table after all pending activations resolved.

- **`login_handshake`**
  - *Purpose:* Stores temporary hashes for “stay logged in” and sitter handshakes.
  - *Issues:* No expiration, uses VARCHAR(40) tokens, lacks relation to devices.
  - *Redesign:*
    - Replace with `personal_access_tokens` (Laravel Sanctum) where `tokenable_id` references `users.id`.
    - Introduce `expires_at`, `last_used_at`, and `ip_address` columns for audit.
    - Provide migration script that copies valid handshakes into Sanctum tokens with generated 80-char secrets.

- **`activation_progress`**
  - *Purpose:* Temporary storage for email change/verification flow.
  - *Issues:* No foreign keys, manual cleanup required, stores plaintext email/token pairs.
  - *Redesign:*
    - Fold into `email_verifications` (shared with activation above) with status enum (`pending`, `completed`, `expired`) defined via Laravel 12 `enum()` migration helper and PHP backed enum cast.
    - Add `metadata` JSON column to capture IP, user agent, locale for security checks.
    - Convert UNIX `time` column to `timestampTz(6)`; add automatic pruning job using `expires_at` and `useCurrentOnUpdate()` semantics.

- **`deleting`**
  - *Purpose:* Schedules account deletions with countdown timers.
  - *Issues:* Uses `uid` without FK, stores `timestamp` integers, lacks reason tracking.
  - *Redesign:*
    - Rename to `account_deletions` with `user_id`, `initiated_at`, `scheduled_for`, `cancelled_at`, `reason`, `initiated_by_admin_id`.
    - Add FK to `users.id` and `users` alias for admin actions.
    - Provide soft delete column to track cancellations.
    - Use `timestampsTz(6)` everywhere to align with Laravel 12 defaults.

- **`newproc`**
  - *Purpose:* Flags users currently going through tutorial/activation processes.
  - *Issues:* Essentially a status table with `uid`, `stage`, `started` integers.
  - *Redesign:*
    - Merge into `user_onboarding_states` with `user_id`, `current_stage`, `step_data` (JSON), `started_at`, `completed_at`.
    - Replace game checks with Eloquent relationships and events leveraging Laravel 12 enum casts for stage transitions.
    - Backfill `stage` into `current_stage`; use JSON to store additional metrics now kept in scattered columns.

**Implementation Checklist:**
- Draft new Laravel migrations reflecting the redesign (using schema builder instead of raw SQL).
- Write data-migration command to port legacy rows into the new structures while keeping IDs stable.
- Update Fortify/Livewire flows to read/write from redesigned tables.
- Decommission legacy tables after validation, leaving archival backups.

### 1.3 Migration Files Creation

- Create ~70+ migration files following Laravel naming conventions
- Order migrations properly (users first, then dependent tables)
- Add indexes for performance (especially timestamp columns)
- Foreign key constraints where appropriate
- Seed files for static data (building types, unit stats, etc.)

---

## Phase 2: Core Models & Authentication (Week 3-4)

### 2.1 Eloquent Models

- **Priority Models (create first):**
- `App\Models\User` (extends Authenticatable)
- `App\Models\Village`
- `App\Models\Alliance`
- `App\Models\Building`
- `App\Models\Unit`
- `App\Models\Attack` (movement system)
- `App\Models\Hero`
- `App\Models\Artifact`
- `App\Models\Report`
- `App\Models\Message`

- **Model Features:**
- Relationships (hasMany, belongsTo, belongsToMany)
- Accessors/Mutators for computed properties
- Scopes for common queries
- Casts for JSON columns, dates
- Events/Observers where needed

### 2.2 Authentication System

- Install Laravel Fortify ^1.31 for authentication (service provider registered in `bootstrap/app.php`)
- Migrate `Model\LoginModel.php` logic to Laravel auth
- Custom guards for admin (uid=0) and multihunter (uid=2)
- Implement email verification flow
- Session management (existing Redis sessions)
- Multi-account detection (IP logging)
- Sitter system (account delegation)

**Files to Migrate:**

- `Controller\LoginCtrl.php` → Fortify actions
- `Controller\RegisterCtrl.php` → Registration
- `Controller\LogoutCtrl.php` → Logout routes
- `Model\LoginModel.php` → Service classes

---

## Phase 3: Game Logic Services (Week 5-8)

    ### 3.1 Service Layer Architecture

    Create service classes for all game mechanics (keep logic exactly as-is):

    **Core Services:**

    - `App\Services\BuildingService` (from `Game\Buildings\BuildingAction.php`)
    - `App\Services\TroopService` (from `Game\TrainingHelper.php`)
    - `App\Services\BattleService` (from `Game\BattleCalculator.php`)
    - `App\Services\HeroService` (from `Game\Hero\HeroHelper.php`)
    - `App\Services\ResourceService` (from `Game\ResourcesHelper.php`)
    - `App\Services\MovementService` (from `Model\MovementsModel.php`)
    - `App\Services\AllianceService` (from `Model\AllianceModel.php`)
    - `App\Services\ArtifactService` (from `Model\ArtefactsModel.php`)
    - `App\Services\MarketService` (from `Model\MarketModel.php`)

**Helper Services:**

- `App\Services\FormulaService` (from `Game\Formulas.php`)
- `App\Services\SpeedCalculator` (from `Game\SpeedCalculator.php`)
- `App\Services\CulturePointsHelper`
- `App\Services\LoyaltyHelper`
- `App\Services\StarvationService`

### 3.2 Configuration Migration

- Move `main_script/include/config.php` complex config to:
- `config/game.php` (game settings)
- `config/buildings.php`
- `config/units.php`
- Database seeders for dynamic config
- Preserve all game balance settings exactly

---

## Phase 4: Background Jobs System (Week 9-10)

### 4.1 Convert Custom Jobs to Laravel Queue

**Current System:**

- `Core\Jobs\Launcher.php` - uses pcntl_fork
- `AutomationEngine.php` - daemon process
- Multiple job types with intervals

**New System:**

- Laravel Queue with database/Redis driver
- Scheduled tasks via `app/Console/Kernel.php`
- Job classes for each background task
- Horizon 5 dashboards configured via `app/Providers/HorizonServiceProvider.php`

**Jobs to Create:**

1. **Building Jobs:**

- `App\Jobs\ProcessBuildingCompletion`
- `App\Jobs\ProcessResearchCompletion`

2. **Movement Jobs:**

- `App\Jobs\ProcessAttackArrival`
- `App\Jobs\ProcessReinforcementArrival`
- `App\Jobs\ProcessReturnMovement`

3. **Training Jobs:**

- `App\Jobs\ProcessTroopTraining`

4. **Game Progress Jobs:**

- `App\Jobs\ProcessDailyQuests`
- `App\Jobs\ProcessMedals`
- `App\Jobs\ProcessArtifacts`
- `App\Jobs\ProcessAllianceBonus`
- `App\Jobs\CheckGameFinish`

5. **AI Jobs:**

- `App\Jobs\ProcessFakeUsers`
- `App\Jobs\ProcessNatarVillages`
- `App\Jobs\ProcessNatarExpansion`
- `App\Jobs\ProcessNatarDefense`

6. **Routine Jobs:**

- `App\Jobs\ProcessAdventures`
- `App\Jobs\ProcessAuctions`
- `App\Jobs\CleanupInactivePlayers`
- `App\Jobs\BackupDatabase`

### 4.2 Scheduled Commands

- `app/Console/Commands/GameEngineCommand.php` - main game loop
- Schedule in `Kernel.php` with appropriate frequencies
- Health monitoring and logging

### 4.3 Migration Services

Maintain the shared service layer between the legacy stack and Laravel so that
authentication, security, and data import flows stay in lockstep.

17. **Auth/LegacyLoginService** – keep the Fortify integration located at
    `backend/app/Services/Auth/LegacyLoginService.php`.
18. **Security/MultiAccountDetector** – reuse the multi-account detection logic
    under `backend/app/Services/Security/MultiAccountDetector.php`.
19. **Migration/DataMigrationService** – orchestrate table imports via
    `backend/app/Services/Migration/DataMigrationService.php`.

---

## Phase 5: Controllers & Routes (Week 11-13)

### 5.1 Convert Controllers to Laravel

**Pattern:** `Controller\{Name}Ctrl.php` → `app/Http/Controllers/{Name}Controller.php`
- Legacy compatibility controllers that remain in `_travian` should extend shared base classes under `app/_travian/Http` and be registered via dedicated namespace groups in `RouteServiceProvider`.
- Align controller namespaces with Laravel 12 `Route::middleware()` groups declared in `bootstrap/app.php`.

**Priority Controllers:**

- `Dorf1Controller` (village overview) - from `Controller\Dorf1Ctrl.php`
- `Dorf2Controller` (building view) - from `Controller\Dorf2Ctrl.php`
- `Dorf3Controller` (village list) - from `Controller\Dorf3Ctrl.php`
- `BuildController` (building actions) - from `Controller\BuildCtrl.php`
- `RallyPointController` - from `Controller\RallyPoint\*`
- `AllianceController` - from `Controller\AllianceCtrl.php`
- `HeroController` - from `Controller\Hero*`
- `MessageController` - from `Controller\NachrichtenCtrl.php`
- `ReportController` - from `Controller\BerichteCtrl.php`
- `StatisticsController` - from `Controller\StatistikenCtrl.php`

**Admin Controllers:**

- `App\Http\Controllers\Admin\*` from `main_script/include/admin/`

### 5.2 API Routes for AJAX

All AJAX endpoints from `Controller\Ajax\*`:

- `routes/api.php` for JSON responses
- Livewire actions for interactive components
- Keep existing AJAX structure where needed for compatibility

### 5.3 Web Routes

- `routes/web.php` - all game routes
- Route model binding for villages, alliances, etc.
- Middleware: auth, game.running, ban.check, etc.
- Configure `RouteServiceProvider` to use Laravel 12's `configureRateLimiting()` signature and map `_travian` module routes where legacy endpoints persist.

---

## Phase 6: Livewire Components (Week 14-17)

### 6.1 Core Game Components

Replace template system with Livewire + Flux UI:

**Village Components:**

- `App\Livewire\VillageOverview` (dorf1)
- `App\Livewire\VillageBuilding` (dorf2)
- `App\Livewire\VillageList` (dorf3)
- `App\Livewire\BuildingQueue`
- `App\Livewire\ResourceProduction`

**Building Components:**

- `App\Livewire\Buildings\Barracks`
- `App\Livewire\Buildings\Academy`
- `App\Livewire\Buildings\Smithy`
- `App\Livewire\Buildings\Marketplace`
- `App\Livewire\Buildings\RallyPoint`
- `App\Livewire\Buildings\Residence`
- `App\Livewire\Buildings\Treasury`
- (One component per building type - ~35 buildings)

**Hero Components:**

- `App\Livewire\Hero\HeroOverview`
- `App\Livewire\Hero\HeroAdventures`
- `App\Livewire\Hero\HeroAuction`
- `App\Livewire\Hero\HeroInventory`

**Communication Components:**

- `App\Livewire\Messages\Inbox`
- `App\Livewire\Messages\Compose`
- `App\Livewire\Reports\ReportList`
- `App\Livewire\Reports\BattleReport`

**Alliance Components:**

- `App\Livewire\Alliance\AllianceProfile`
- `App\Livewire\Alliance\AllianceForum`
- `App\Livewire\Alliance\AllianceMembers`
- `App\Livewire\Alliance\AllianceDiplomacy`

**Map Components:**

- `App\Livewire\Map\MapView`
- `App\Livewire\Map\TileDetails`

### 6.2 Flux UI Integration

- Use Flux components for buttons, forms, modals
- Consistent design system across all pages
- Real-time updates for resources, timers
- Toast notifications for events

---

## Phase 7: Views & Frontend (Week 18-20)

### 7.1 Layout Structure

- `resources/views/layouts/app.blade.php` - main game layout
- `resources/views/layouts/guest.blade.php` - login/register
- `resources/views/layouts/admin.blade.php` - admin panel
- Preserve existing CSS/JS structure initially

### 7.2 Template Migration

**Current:** `main_script/include/resources/Templates/*`
**Target:** `resources/views/*`

- Convert PHP templates to Blade components
- Use `@livewire()` directives for interactive parts
- Maintain existing game graphics/sprites
- Keep Angular frontend (`angularIndex/`) for now if needed

### 7.3 Assets

- Copy existing images, CSS, JS to `public/`
- Vite configuration for asset bundling
- Preserve game sprites and graphics exactly

---

## Phase 8: Data Migration & Testing (Week 21-23)

### 8.1 Database Migration Script

**Critical:** Zero downtime migration strategy

1. Create `app/Console/Commands/MigrateOldDataCommand.php`
2. Map old schema → new schema
3. Migrate in chunks to prevent timeouts
4. Validate data integrity after migration
5. Preserve all timestamps, relationships

**Tables requiring special handling:**

- Users (password hashing check)
- Villages (coordinate system)
- Attacks/movements (in-flight during migration)
- Building/training queues (active timers)

### 8.2 Parallel Run Period

- Run old system and Laravel side-by-side
- Route new logins to Laravel
- Keep existing sessions on old system
- Monitor for bugs/differences

### 8.3 Testing

- Unit tests for game formulas
- Feature tests for critical flows (battle, building, trading)
- Integration tests for job system
- Load testing for concurrent users
- Test all 5 tribes (Romans, Teutons, Gauls, Egyptians, Huns)

---

## Phase 9: Admin Panel & Tools (Week 24-25)

### 9.1 Admin Interface

Migrate `main_script/include/admin/` to Laravel:

- `App\Livewire\Admin\Dashboard`
- `App\Livewire\Admin\PlayerManagement`
- `App\Livewire\Admin\VillageEditor`
- `App\Livewire\Admin\GiftSystem`
- `App\Livewire\Admin\PunishmentSystem`
- `App\Livewire\Admin\ServerSettings`

### 9.2 Monitoring & Logs

- Laravel Telescope for debugging
- Custom logging for game events
- Performance monitoring
- Error tracking (Sentry optional)

---

## Phase 10: Optimization & Deployment (Week 26-27)

### 10.1 Performance Optimization

- Database query optimization
- Eloquent N+1 query prevention
- Redis caching strategy
- Queue worker scaling
- Horizon for queue monitoring

### 10.2 Deployment

- Update Kubernetes ingress manifests to route traffic to Laravel app
- Roll out Supervisor-managed queue workers and scheduler processes
- Laravel Octane (optional for performance)
- Ensure Redis 6+ is provisioned for cache/queue/session drivers

### 10.3 Cutover Plan

1. Announce maintenance window
2. Stop old automation engine
3. Migrate final data snapshot
4. Shift ingress routing to Laravel app
5. Start Laravel queue workers
6. Monitor for 24-48 hours
7. Keep old system as backup for 1 week

---

## Key Files Mapping Reference

| Old Path | New Path | Type |
|----------|----------|------|
| `main_script/include/Model/PlayerModel.php` | `app/Models/User.php` | Model |
| `main_script/include/Model/VillageModel.php` | `app/Models/Village.php` | Model |
| `main_script/include/Controller/Dorf1Ctrl.php` | `app/Http/Controllers/VillageController.php` | Controller |
| `main_script/include/Game/BattleCalculator.php` | `app/Services/BattleService.php` | Service |
| `main_script/include/Core/Jobs/Launcher.php` | `app/Console/Kernel.php` + Jobs | Jobs |
| `main_script/include/resources/Templates/dorf1/main.php` | `resources/views/livewire/village-overview.blade.php` | View |
| `main_script/include/config.php` | `config/game.php` | Config |

---

## Critical Considerations

1. **Game Balance:** All formulas, timings, costs MUST remain identical
2. **Active Timers:** Building/training/movement timers must transition seamlessly
3. **Sessions:** User sessions must not be invalidated during migration
4. **Real-time Updates:** Resource tick system must continue without interruption
5. **Multi-server Support:** TaskWorker system for managing multiple game worlds
6. **Redis Sessions:** Maintain existing session structure initially
7. **IP Logging:** Preserve multi-account detection system
8. **Sitter System:** Account delegation must work identically

---

## Phase 11: Documentation, Training & Launch Readiness (Week 28)

### 11.1 Developer & Operations Playbooks

- Draft "day-one" runbooks covering deployment, queue scaling, cache purges, and rollback.
- Capture troubleshooting guides for battle resolution, stuck jobs, Redis outages, and slow queries.
- Produce ER diagrams and sequence diagrams that map the redesigned schema to major user flows.

### 11.2 Support & Community Enablement

- Update customer support macros to match new terminology (alliances, sitter invites, etc.).
- Record short screencasts demonstrating the Livewire UI for village management, hero adventures, and alliance forums.
- Prepare FAQ entries covering login changes, email verification, and how to report suspected bugs during the stabilisation window.

### 11.3 Launch Checklist & Go/No-Go Gates

- Establish objective success metrics (p95 response time < 250ms, queue latency < 2s, zero critical errors).
- Run final chaos drills (Redis restart, DB failover, queue saturation) using staging data.
- Hold go/no-go meeting with engineering, operations, community managers, and stakeholders to sign-off before cutover.

---

## Risk Register & Mitigation Strategies

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| **Schema migration data loss** | Medium | Critical | Dry-run migrations on production snapshot, automated diff checks, transaction-scoped imports per table. |
| **Performance regression under load** | High | High | Early load testing with realistic troop movements, enable Laravel Telescope + Blackfire profiling, cache hot paths. |
| **Queue backlog after cutover** | Medium | High | Scale Supervisor workers horizontally, configure Horizon alerts, pre-warm Redis. |
| **Legacy client incompatibility** | Low | Medium | Maintain compatibility endpoints, versioned API responses, staged rollout for classic clients. |
| **Team unfamiliarity with Laravel 12** | Medium | Medium | Formal Livewire & Horizon workshops, pair migration sessions, internal wiki for patterns. |
| **Security regressions** | Low | Critical | Fortify hardening review, mandatory code reviews, OWASP ZAP scan before launch. |

---

## Resource Planning Snapshot

- **People:**
  - 1x Technical Lead (overall ownership)
  - 2x Backend Engineers (schema, services, jobs)
  - 2x Frontend Engineers (Livewire, Flux UI, Blade)
  - 1x DevOps Engineer (infrastructure, CI/CD, monitoring)
  - 1x QA Engineer (automation, load testing)
  - 1x Community/Support Liaison (communication, documentation)
- **Tooling Budget:** Allocate credits/licenses for Blackfire, Sentry, and Mailhog/SMTP testing.
- **Environment Matrix:** Production, Staging (full data), QA (sanitised subset), Developer sandboxes (per engineer with seed data).
- **Time Buffers:** Reserve 15% of each phase for bug-fixing and regression testing to absorb unknowns without derailing critical path.

---

## Success Metrics & Post-Launch Monitoring

- **Stability KPIs:** Error rate < 0.1%, zero failed queued jobs > 5 minutes, uptime ≥ 99.9% during first 30 days.
- **Performance KPIs:** Average resource tick execution < 120ms, hero adventure resolution < 2s, map view load time < 1.5s.
- **Engagement KPIs:** Daily active users parity with legacy platform by week two, 95%+ successful login rate, reduction in support tickets week-over-week.
- **Monitoring Stack:** Horizon alerts (queue backlog), Prometheus/Grafana dashboards (PHP-FPM, Redis, DB), Sentry alerting for high-severity exceptions.
- **Feedback Loop:** Daily triage stand-ups for first two weeks post-launch, single Slack channel for bug reports, weekly retrospective summarising fixes and learnings.























# Travian T4.6 → Laravel 12 Complete Migration Plan

## Project Overview

Migrate the legacy TravianT4.6 browser game from a custom PHP 7.3-7.4 framework to Laravel 12 with Livewire 3, using the existing `/backend` Laravel auth foundation as the base and merging all old Travian game mechanics.

**Key Statistics:**

- **Old Codebase:** 90 database tables, 45 models, 80+ controllers, 200+ templates, custom MVC framework
- **Backend Laravel:** Auth system with Fortify, 8 migrations, 7 game models, sitter system, multi-account detection
- **Target:** Unified Laravel 12 application in project root with pure Livewire architecture

### Historical Context & Stakeholders

- **Product Vision:** Deliver a modernised Travian experience with faster iteration cycles, better anti-cheat visibility, and a Livewire-first UI that mirrors current T4.6 mechanics without alienating veteran players.
- **Primary Stakeholders:**
  - **Game Studio Leadership:** Approves roadmap and budget; requires weekly burndown reports and migration readiness checkpoints.
  - **Operations/SRE:** Own Kubernetes ingress, cluster capacity planning, and incident management runbooks.
  - **Community Management:** Coordinates announcements, forum updates, and change logs for tournament worlds.
  - **Support & Moderation:** Relies on admin tooling, ban history, and sitter oversight features to maintain game integrity.
  - **Analytics & Monetisation:** Depends on real-time purchase data, auction telemetry, and gold balance accuracy for revenue reporting.
- **External Partners:** Translation vendors (12 locales), payment processors, and anti-fraud monitoring service (FraudLabs Pro) consuming webhook notifications.

### Environment & Infrastructure Baseline

- **Hosting:** Bare-metal origin servers fronted by Kubernetes ingress gateways in two regions (Frankfurt primary, Dallas secondary) connected via VPN to legacy automation hosts.
- **CI/CD Today:** Jenkins pipelines triggered by SVN commits; migration requires porting to GitHub Actions with artifact promotion into ArgoCD-managed Helm releases.
- **Secrets Management:** Environment variables currently stored in `.ini` files; target platform standardises on Vault-issued dynamic secrets injected via Laravel's `config/secrets.php` bridge.
- **Observability:** Logs are shipped to Elasticsearch via Filebeat; metrics captured through Prometheus exporters deployed alongside PHP-FPM pods.
- **Disaster Recovery:** RPO of 1 hour, RTO of 4 hours; restore testing occurs quarterly using cold standby environment that mirrors production minus payment integrations.

### Governance & Communication Plan

- Bi-weekly steering committee sync covering migration health, budget consumption, and risk review.
- Daily migration stand-up covering blockers across backend, frontend, and DevOps streams.
- Dedicated Slack channels: `#migration-core`, `#migration-alerts`, `#migration-livewire` with on-call rotations pinned.
- Change management checklist aligned with corporate PMO process—requires sign-off from Legal when user-facing policy updates occur.
- Stakeholder newsletter summarising achievements, upcoming cutover rehearsals, and pending decisions every Friday.

## Phase 1: File Reorganization & Git Sync

### 1.1 Move Backend Laravel to Root

- Move all Laravel files from `/backend/*` to project root
- Merge `backend/composer.json` with root `composer.json` (preserve Laravel 12 dependencies)
- Update all namespace references and paths in moved files
- Move `.env.example` from backend to root (if exists)

### 1.2 Archive Old Travian Files

- Create `/_travian` directory
- Move old Travian codebase to `/_travian` with reorganized structure:
- `/_travian/controllers/` ← from `main_script/include/Controller/`
- `/_travian/models/` ← from `main_script/include/Model/`
- `/_travian/services/` ← from `main_script/include/Game/`
- `/_travian/views/` ← from `main_script/include/resources/Templates/`
- `/_travian/public/` ← from `main_script/copyable/public/`
- `/_travian/config/` ← from `main_script/include/config/`
- `/_travian/schema/` ← from `main_script/include/schema/`
- `/_travian/core/` ← from `main_script/include/Core/`
- `/_travian/legacy/` ← all other old files (sections/, services/, TaskWorker/, etc.)

### 1.3 Clean Root Directory

- Remove old files from root: `index.php`, `dbbackup.php`, old `composer.json` (non-Laravel), old `resources/`, old `database/` (after backing up)
- Keep only Laravel project files in root
- Update `.gitignore` for Laravel structure

### 1.4 Git Operations

- Stage all changes
- Commit with message: "Migrate to Laravel 12: Move backend to root, archive old Travian to _travian/"
- Push to remote repository

### 1.5 Create AGENT.md

- Comprehensive task documentation
- Migration checklist with 200+ items
- File mapping reference (old → new)
- Database schema redesign specifications
- Service layer architecture
- Testing requirements

## Phase 2: Database Schema Redesign (90 Tables)

### 2.1 Analyze All Tables

Extract complete schema from `/_travian/schema/T4.4.sql` covering:

- **Auth/User (6 tables):** users, activation, login_handshake, activation_progress, deleting, newproc
- **Villages (11 tables):** vdata, fdata, wdata, available_villages, odata, building_upgrade, demolition, smithy, tdata, research, traderoutes
- **Alliances (16 tables):** alidata, alistats, allimedal, ali_invite, ali_log, diplomacy, alliance_notification, alliance_bonus_upgrade_queue, forum_*
- **Combat/Movement (8 tables):** movement, a2b, enforcement, trapped, units, send
- **Hero (6 tables):** hero, face, items, inventory, adventure, accounting
- **Artifacts (3 tables):** artefacts, artlog
- **Communication (4 tables):** mdata, ndata, messages_report, notes
- **Market (4 tables):** market, auction, bids, traderoutes, raidlist
- **Map (7 tables):** map_block, map_mark, mapflag, marks, blocks, surrounding
- **Quests/Medals (3 tables):** daily_quest, medal, allimedal
- **Farmlist (3 tables):** farmlist, raidlist, farmlist_last_reports
- **Admin/Logs (9 tables):** general_log, admin_log, log_ip, transfer_gold_log, banHistory, banQueue, multiaccount_log, multiaccount_users
- **Config (4 tables):** config, summary, casualties, autoExtend
- **Misc (11 tables):** links, infobox, infobox_read, infobox_delete, ignoreList, friendlist, changeEmail, notificationQueue, voting_reward_queue, buyGoldMessages, player_references

### 2.2 Design Laravel-Compliant Migrations

For EACH table:

- Convert to snake_case column names (NO `u1-u10`, `f1-f40` generic names)
- Add `id` as BIGINT UNSIGNED primary key
- Add `created_at`, `updated_at` timestamps
- Convert INT(10) timestamps to TIMESTAMP columns
- Add proper foreign key constraints
- Add indexes for performance (timestamps, foreign keys, frequently queried columns)
- Use descriptive column names (e.g., `u1` → `infantry_count`, `f1` → `woodcutter_level`)

**Priority Redesigns:**

1. **users table:** Merge with backend/migrations users, add Fortify columns (email_verified_at, remember_token, two_factor_secret), alliance relationships, gold/silver balances
2. **villages (vdata):** Redesign with proper relationships to users, coordinates indexed, population, loyalty
3. **village_buildings (fdata):** Replace `f1-f40` with normalized structure (village_id, building_type_id, level)
4. **village_units (units):** Replace `u1-u10` with normalized structure (village_id, unit_type_id, count)
5. **attacks (a2b):** Rename and redesign with readable column names
6. **movements table:** Add proper relationships, movement types enum
7. **alliances (alidata):** Merge alliance data with proper relationships
8. **hero table:** Keep existing backend structure, add missing columns from old schema
9. **adventures table:** Keep existing backend structure, validate against old schema

### 2.3 Create Migration Files

- Create 90+ migration files in `database/migrations/` following order:

1. Core tables (users, config, summary)
2. Game world tables (wdata, vdata, odata)
3. Village-dependent tables (fdata, units, hero)
4. Movement tables (movement, a2b, enforcement)
5. Alliance tables (alidata, forum_*)
6. Communication tables (mdata, ndata)
7. Supporting tables (artifacts, market, quests)

### 2.4 Create Seeders

- `BuildingTypesSeeder`: All 40 building types with stats
- `UnitTypesSeeder`: All unit types for 5 tribes (Romans, Teutons, Gauls, Egyptians, Huns)
- `GameConfigSeeder`: Initial game configuration from old config table
- `TribeSeeder`: Tribe definitions and bonuses
- `ResearchTreeSeeder`: Technology research requirements

## Phase 3: Eloquent Models (90+ Models)

### 3.1 Core Models

- **User** (extend existing backend/app/Models/User.php)
- Add relationships: villages(), alliance(), hero(), messages(), reports()
- Add accessors: gold_balance, silver_balance, tribe_name
- Add scopes: active(), banned(), tribe()
- Preserve Fortify authentication traits

- **Village** (enhance existing backend/app/Models/Game/Village.php)
- Relationships: owner(), buildings(), units(), movements(), resources()
- Accessors: coordinates, total_population, production_rates
- Scopes: capital(), byCoordinates(), inRadius()

- **Alliance**
- Relationships: members(), diplomacy(), forum(), bonuses()
- Accessors: total_population, total_villages, rank

- **Building** (redesign from backend VillageBuilding)
- Polymorphic relationship to building types
- Methods: upgrade(), demolish(), canBuild()

- **Unit** (redesign from backend VillageUnit)
- Relationships: unitType(), village(), movements()
- Methods: train(), merge(), split()

### 3.2 Game Mechanics Models

- **Attack** (from a2b table)
- **Movement** (keep backend structure, enhance)
- **Hero** (merge backend with old schema)
- **HeroInventory**, **HeroItem**, **HeroAdventure** (keep backend, enhance)
- **Artifact**, **ArtifactLog**
- **Message**, **Report** (from mdata, ndata)
- **Market**, **Auction**, **Bid**, **TradeRoute**
- **DailyQuest**, **Medal**
- **Farmlist**, **RaidList**

### 3.3 Supporting Models

- **BuildingType**, **UnitType**, **ResearchLevel**
- **AllianceForum**, **ForumTopic**, **ForumPost**
- **MapTile** (from wdata), **Oasis** (from odata)
- **LoginActivity** (keep from backend), **MultiAccountAlert** (keep)
- **SitterDelegation** (keep from backend)

## Phase 4: Service Layer (30+ Services)

### 4.1 Core Game Services

Migrate logic from `/_travian/services/` and `/_travian/models/`:

1. **BuildingService** ← `Game/Buildings/BuildingAction.php`

- Methods: canUpgrade(), upgrade(), demolish(), calculateBuildTime()
- Preserve all formulas exactly

2. **TroopService** ← `Game/TrainingHelper.php`

- Methods: train(), calculateTrainingTime(), canTrain()

3. **BattleService** ← `Game/BattleCalculator.php`

- Methods: simulateBattle(), calculateCasualties(), distributeLoot()
- CRITICAL: Preserve exact battle formulas

4. **HeroService** ← `Game/Hero/HeroHelper.php`

- Methods: gainExperience(), levelUp(), equipItem(), startAdventure()

5. **ResourceService** ← `Game/ResourcesHelper.php`

- Methods: calculateProduction(), updateResources(), checkStorage()
- Real-time resource tick system

6. **MovementService** ← `Model/MovementsModel.php`

- Methods: sendAttack(), sendReinforcement(), sendTrade(), calculateArrival()

7. **AllianceService** ← `Model/AllianceModel.php`

- Methods: create(), invite(), kick(), manageDiplomacy()

8. **ArtifactService** ← `Model/ArtefactsModel.php`

- Methods: capture(), activate(), applyEffects()

9. **MarketService** ← `Model/MarketModel.php`

- Methods: createOffer(), sendResources(), calculateMerchants()

### 4.2 Helper Services

10. **FormulaService** ← `Game/Formulas.php`
11. **SpeedCalculator** ← `Game/SpeedCalculator.php`
12. **CulturePointsHelper** ← `Game/Helpers/CulturePointsHelper.php`
13. **LoyaltyHelper** ← `Game/Helpers/LoyaltyHelper.php`
14. **StarvationService** ← `Game/Starvation.php`
15. **NoticeService** ← `Game/NoticeHelper.php`
16. **GoldService** ← `Game/GoldHelper.php`

### 4.3 Migration Services

17. **Auth/LegacyLoginService** (keep from backend Services/Auth/)
18. **Security/MultiAccountDetector** (keep from backend Services/Security/)
19. **Migration/DataMigrationService** (keep from backend Services/Migration/)

## Phase 5: Background Jobs & Queue System

### 5.1 Core Jobs (keep existing from backend, add more)

Existing (from backend/app/Jobs/):

- ProcessAdventures.php
- ProcessBuildingCompletion.php
- ProcessTroopTraining.php
- ProcessServerTasks.php

### 5.2 New Jobs to Create

From `/_travian/core/Core/Jobs/`:

**Building & Research:**

- ProcessResearchCompletion
- ProcessDemolition

**Movement & Combat:**

- ProcessAttackArrival (from `Model/Movements/`)
- ProcessReinforcementArrival
- ProcessReturnMovement
- ProcessSettlerArrival
- ProcessEvasion

**Resource Management:**

- ProcessResourceTick (every minute)
- ProcessStarvation (crop shortage)
- ProcessStorageOverflow

**Game Progress:**

- ProcessDailyQuests
- ProcessMedals
- ProcessArtifactEffects
- ProcessAllianceBonus
- CheckGameFinish (Wonder of the World)

**AI & Automation:**

- ProcessFakeUsers (AI players)
- ProcessNatarExpansion (NPCs)
- ProcessNatarDefense

**Market & Economy:**

- ProcessTradeRoutes
- ProcessAuctionEnd
- ProcessMerchantReturn

**Cleanup & Maintenance:**

- CleanupInactivePlayers
- CleanupOldReports
- CleanupOldMessages
- BackupDatabase

### 5.3 Scheduled Commands

In `app/Console/Kernel.php` schedule:

- Resource tick: every 1 minute
- Adventures: every 5 minutes
- Building/training completion: every 1 minute
- Movement processing: every 10 seconds (critical)
- Daily quests reset: daily at midnight
- Cleanup jobs: daily

## Phase 6: Controllers → Livewire Components

### 6.1 Replace Controllers with Livewire

NO traditional controllers (per user preferences). Convert ALL 80+ controllers to Livewire components:

**Village Management:**

- `App\Livewire\Village\Overview` ← Dorf1Ctrl.php
- `App\Livewire\Village\BuildingView` ← Dorf2Ctrl.php
- `App\Livewire\Village\VillageList` ← Dorf3Ctrl.php
- `App\Livewire\Village\ResourceFields` ← ProductionCtrl.php

**Building Components (35 buildings):**

- `App\Livewire\Buildings\Barracks` ← Controller/Build/Barracks.php
- `App\Livewire\Buildings\Academy`
- `App\Livewire\Buildings\Smithy`
- `App\Livewire\Buildings\Marketplace`
- `App\Livewire\Buildings\RallyPoint` ← Controller/RallyPoint/
- `App\Livewire\Buildings\Residence`
- `App\Livewire\Buildings\Treasury`
- `App\Livewire\Buildings\TownHall`
- `App\Livewire\Buildings\Embassy`
- ... (one component per building type)

**Hero System:**

- `App\Livewire\Hero\Overview` ← HeroBodyCtrl.php
- `App\Livewire\Hero\Adventures` ← HeroAdventureCtrl.php
- `App\Livewire\Hero\Auction` ← HeroAuctionCtrl.php
- `App\Livewire\Hero\Inventory` ← HeroInventoryCtrl.php
- `App\Livewire\Hero\Appearance` ← HeroFaceCtrl.php

**Communication:**

- `App\Livewire\Messages\Inbox` ← NachrichtenCtrl.php
- `App\Livewire\Messages\Compose`
- `App\Livewire\Reports\List` ← BerichteCtrl.php
- `App\Livewire\Reports\BattleReport`
- `App\Livewire\Reports\TradeReport`

**Alliance:**

- `App\Livewire\Alliance\Profile` ← AllianceCtrl.php
- `App\Livewire\Alliance\Members`
- `App\Livewire\Alliance\Forum` ← AllianceForum.php
- `App\Livewire\Alliance\Diplomacy`
- `App\Livewire\Alliance\Bonuses`

**Map:**

- `App\Livewire\Map\WorldMap` ← KarteCtrl.php
- `App\Livewire\Map\TileDetails` ← PositionDetailsCtrl.php
- `App\Livewire\Map\Minimap` ← MinimapCtrl.php

**Statistics & Rankings:**

- `App\Livewire\Statistics\Players` ← StatistikenCtrl.php
- `App\Livewire\Statistics\Alliances`
- `App\Livewire\Statistics\Villages`
- `App\Livewire\Statistics\Heroes`

**Admin Panel:**

- `App\Livewire\Admin\Dashboard`
- `App\Livewire\Admin\PlayerManagement`
- `App\Livewire\Admin\VillageEditor`
- `App\Livewire\Admin\GiftSystem`
- `App\Livewire\Admin\BanSystem`
- `App\Livewire\Admin\ServerSettings`

### 6.2 AJAX Endpoints → Livewire Actions

Convert ALL 60+ AJAX endpoints from `Controller/Ajax/` to Livewire component methods:

- NO separate API routes
- Use Livewire `#[On]` events and public methods
- Real-time updates via Livewire wire:poll

## Phase 7: Views & Frontend

### 7.1 Blade Templates

Convert 200+ PHP templates from `/_travian/views/Templates/` to Blade:

**Layouts:**

- `resources/views/layouts/game.blade.php` (main game layout with header, sidebar, footer)
- `resources/views/layouts/guest.blade.php` (login/register from backend)
- `resources/views/layouts/admin.blade.php`

**Components:**

- `resources/views/components/resource-bar.blade.php` (wood, clay, iron, crop display)
- `resources/views/components/building-card.blade.php`
- `resources/views/components/timer.blade.php` (countdown timers)
- `resources/views/components/unit-icon.blade.php`
- `resources/views/components/tribe-icon.blade.php`

### 7.2 Flux UI Integration

Use Flux UI components (already in backend dependencies):

- Buttons, forms, modals, dropdowns
- Toast notifications for game events
- Real-time data tables for units, buildings

### 7.3 Assets Management

- Copy game graphics from `/_travian/public/img/` to `public/img/`
- Preserve sprite sheets, building images, unit icons
- Copy CSS from `/_travian/public/css/` to `resources/css/` (convert to Tailwind if possible)
- Copy JavaScript from `/_travian/public/js/` to `resources/js/`
- Configure Vite for asset bundling (already setup in backend)

### 7.4 Angular Frontend

- Keep `angularIndex/` folder for now (if still needed)
- Plan separate migration for Angular components later OR replace with Livewire

## Phase 8: Configuration Migration

### 8.1 Game Configuration

Create `config/game.php` from `/_travian/config/config.php`:

- Game speed multipliers
- Resource production rates
- Building costs and requirements
- Unit stats and costs
- World size and map settings
- Tribe bonuses
- Wonder of the World settings

### 8.2 Building Configuration

Create `config/buildings.php` with all 40 building types:

- Building IDs, names, max levels
- Resource costs per level
- Build time calculations
- Prerequisites
- Special effects

### 8.3 Unit Configuration

Create `config/units.php` for all 5 tribes:

- Unit stats (attack, defense, speed, carry capacity)
- Training costs and times
- Tribe-specific bonuses
- Special abilities

### 8.4 Keep Existing Backend Config

Preserve from `backend/config/`:

- auth.php, fortify.php (authentication)
- database.php, redis.php
- queue.php, logging.php

## Phase 9: Data Migration Scripts

### 9.1 Migration Command

Create `app/Console/Commands/MigrateOldDataCommand.php`:

- Read from old database tables
- Transform data to new schema
- Insert into new tables in chunks
- Validate data integrity
- Handle special cases

### 9.2 Critical Tables (special handling)

**users:** Preserve user IDs, hash passwords if needed, map old columns to new
**villages:** Preserve village IDs, coordinates, update relationships
**movements:** In-flight attacks/reinforcements must continue
**building_upgrade:** Active building queues
**training:** Active troop training
**hero/inventory:** Preserve hero stats and items
**alliances:** Preserve alliance IDs and relationships

### 9.3 Testing Migration

- Dry-run on copy of production database
- Validate all foreign keys
- Check data integrity (counts, sums)
- Test that game mechanics still work

## Phase 10: Routing & Authentication

### 10.1 Web Routes

Update `routes/web.php`:

- Keep backend auth routes (login, register, logout, verification)
- Add game routes (ALL using Livewire components, NO controllers)
- Middleware: auth, verified, game.running, ban.check

### 10.2 Middleware

Create custom middleware:

- `CheckGameRunning`: Verify game is not in maintenance
- `CheckBanned`: Redirect banned users
- `CheckSitter`: Handle sitter context
- `UpdateResources`: Update resources on each request
- `CheckVillageOwnership`: Verify user owns village

### 10.3 Guards (keep from backend)

- default (regular players)
- admin (uid=0)
- multihunter (uid=2)

## Phase 11: Testing

### 11.1 Unit Tests

- Game formulas (battle calculator, production, speed)
- Services (building, training, movement)
- Models (relationships, scopes, accessors)

### 11.2 Feature Tests

The feature test suite exercises end-to-end player journeys using production-like data fixtures, a seeded world map, and the canonical queue/cron configuration. Every scenario runs against a clean database snapshot and validates telemetry events emitted to the event bus.

#### Authentication flow

| Scenario | Pre-conditions | Steps | Expected Result |
| --- | --- | --- | --- |
| First-time login | Player account exists with confirmed email and no active session | 1. Submit login form with valid credentials.<br>2. Accept updated terms modal.<br>3. Redirect to tutorial village. | Session cookie issued, player context cached, tutorial flag set, login audit row created. |
| Invalid password lockout | Account exists, 4 failed attempts logged within 15 minutes | 1. Submit wrong password twice more.<br>2. Attempt login with correct password. | Account enters temporary lockout, lockout timer visible, telemetry `auth.lockout` event recorded, no session started. |

#### Village creation

| Scenario | Pre-conditions | Steps | Expected Result |
| --- | --- | --- | --- |
| Tutorial completion | Fresh account after authentication flow | 1. Complete tutorial tasks.<br>2. Trigger automatic village placement. | Village row created with spawn coordinates, starting resources seeded, `village_created` notification queued. |
| Manual expansion | Account owns >=1 village, has expansion slot unlocked | 1. Send settler party to empty tile.<br>2. Wait for travel timer.<br>3. Resolve settlement event. | New village recorded, settlers consumed, culture points deducted, map tile ownership updated. |

#### Building upgrade

| Scenario | Pre-conditions | Steps | Expected Result |
| --- | --- | --- | --- |
| Resource field upgrade | Village has level 1 woodcutter, sufficient wood/clay/iron/crop | 1. Initiate upgrade to level 2.<br>2. Accelerate timer via plus account item. | Queue entry created, resource costs deducted immediately, completion upgrades building stats, event appears in activity log. |
| Infrastructure upgrade blocked | Warehouse at capacity limit | 1. Attempt to upgrade barracks requiring additional storage. | Upgrade rejected with validation message, no resources spent, analytics `build.blocked.storage` emitted. |

#### Troop training

| Scenario | Pre-conditions | Steps | Expected Result |
| --- | --- | --- | --- |
| Standard training | Barracks level ≥1, resources available | 1. Queue 10 infantry units.<br>2. Let timer complete. | Training queue entries created, resources consumed, population increases when batch completes, troops appear in rally point overview. |
| Queue cancellation refund | Training queue active | 1. Cancel remaining units mid-queue. | Refund proportional resources, remaining units removed, cancellation logged in troop audit table. |

#### Attack sending and resolution

| Scenario | Pre-conditions | Steps | Expected Result |
| --- | --- | --- | --- |
| Raid attack | Attacker village has troops, target has resources | 1. Send raid.<br>2. Allow battle resolution job to run. | Combat simulator invoked, losses calculated, loot transferred, battle report generated and delivered to both players. |
| Reinforcement mis-send | Attempt to reinforce enemy alliance | 1. Select reinforcement mission to village with hostile diplomacy status. | UI blocks action, API returns 409 with validation error, no troop movement created. |

#### Market trades

| Scenario | Pre-conditions | Steps | Expected Result |
| --- | --- | --- | --- |
| Player-to-player trade | Marketplace level ≥1, merchants idle | 1. Post trade offer.<br>2. Second player accepts offer.<br>3. Merchants travel and deliver goods. | Offer persisted, matching logic reserves merchants, ledger records both debit/credit entries, trade report issued on completion. |
| Alliance trade tax waiver | Both players share alliance with tax perk | 1. Initiate internal trade. | Trade executes without tax deduction, alliance ledger updated for perk usage, audit confirms zero fee. |

#### Alliance management

| Scenario | Pre-conditions | Steps | Expected Result |
| --- | --- | --- | --- |
| Invite and accept | Alliance exists with open invitation slot | 1. Leader sends invite.<br>2. Target player accepts via inbox. | Invitation token generated, acceptance joins member with default role, alliance statistics recalculated, welcome message posted. |
| Role privilege enforcement | Member with diplomat role only | 1. Attempt to kick another member. | Operation denied, UI shows insufficient rights, no change to membership table, security log records attempt. |

#### Hero adventures

| Scenario | Pre-conditions | Steps | Expected Result |
| --- | --- | --- | --- |
| Adventure completion | Hero idle, adventure available | 1. Dispatch hero.<br>2. Wait for return timer.<br>3. Resolve adventure. | Hero gains XP and loot according to template, health deducted, adventure status set to completed, hero inventory updated. |
| Hero defeat handling | Hero low health, adventure difficulty high | 1. Send hero with <15% health to hard adventure. | Hero defeated, revival timer set, inventory unchanged, notification queued, adventure slot freed for respawn. |

### 11.3 Integration Tests

- Queue job processing
  - Use seeded events (building upgrades, troop movements, resource ticks) to populate the `event_queue` table and confirm Laravel queue workers drain the jobs without dead-lettering.
  - Instrument Horizon dashboard metrics and Redis queue depth to ensure throughput stays within SLA under sustained load (500 jobs/minute baseline, 1,500 jobs/minute burst).
  - Validate idempotency by replaying the same payload twice and confirming ledger/accounting tables remain consistent.
- Real-time resource updates
  - Run the resource tick processor alongside a websocket subscriber to confirm the `village_resources` table updates are broadcast to connected clients within 3 seconds.
  - Simulate cache invalidation (Redis eviction) to verify clients gracefully refetch from the API without stale totals.
  - Capture Prometheus metrics (`resource_tick_duration_seconds`) to ensure p95 latency < 200ms and no missed ticks across a 30 minute window.
- Concurrent attacks
  - Spin up three attacking villages targeting the same defender via API to observe queue ordering, troop stack deductions, and battle resolution consistency.
  - Confirm battle reports generate once per engagement and link to the correct participants, even when collisions occur within the same second.
  - Assert that post-battle resource plundering updates both `resource_ledger` and `village_resources` atomically.
- Session management
  - Execute login from two devices for the same account, validating session tokens issuance, revocation on logout, and idle timeout behavior.
  - Verify session fixation protection by rotating tokens after privilege escalation (e.g., account settings update).
  - Confirm Redis-backed session storage survives failover by forcing a replica promotion during active gameplay.
- Multi-account detection
  - Seed analytics events to trigger IP/device fingerprint correlations and ensure flagged accounts appear in the review queue with supporting evidence.
  - Validate alert workflow by acknowledging a flagged account and confirming downstream webhook delivery to the moderation tool.
  - Run a regression scenario where sibling accounts share a network (e.g., campus IP) to ensure heuristics respect allowlists before escalating.

## Phase 12: Deployment & Cutover

### 12.1 Environment Setup

- Kubernetes ingress configuration pointing to `resources/web/public/` symlinked to Laravel 12 `public/`
- PHP 8.2+ with required extensions
- Redis 6+ for cache/queue/sessions
- MariaDB/MySQL 10.6+
- Supervisor for queue workers

### 12.2 Queue Workers

Configure Supervisor to run:

- 3-5 general queue workers
- 1 dedicated movement processor (high priority)
- 1 resource tick processor
- Laravel scheduler (cron)

### 12.3 Cutover Plan

1. Maintenance mode on old system
2. Final data migration
3. Update ingress target to Laravel public/
4. Start queue workers
5. Monitor for 24-48 hours
6. Keep old system as backup for 1 week

## Success Metrics

- Zero data loss during migration
- All 90 tables migrated with proper relationships
- All game mechanics preserved (battle formulas, timers, etc.)
- Performance: <250ms page load, <2s queue processing
- All tests passing (100+ tests)
- Zero critical errors in first 48 hours

## Stakeholder Sign-off & Distribution

- Circulate this Laravel 12 migration plan to Product, SRE, and Game Design leads via the migration Confluence space for formal approval.
- Capture sign-off decisions in the change management ticket and update the status in weekly program reviews.
- Link back to this plan from `AGENT.md` so future automation tasks reference the latest agreed-upon strategy.
- Archive superseded Laravel 11 planning documents in `/docs/archive/2024-migration/` after approval is recorded.

## File Mapping Reference

| Old Path | New Path | Type |
|----------|----------|------|
| `backend/*` | `/` (root) | Laravel Base |
| `main_script/include/Model/PlayerModel.php` | `app/Models/User.php` | Model |
| `main_script/include/Model/VillageModel.php` | `app/Models/Village.php` | Model |
| `main_script/include/Controller/Dorf1Ctrl.php` | `app/Livewire/Village/Overview.php` | Livewire |
| `main_script/include/Game/BattleCalculator.php` | `app/Services/BattleService.php` | Service |
| `main_script/include/resources/Templates/` | `resources/views/` | Views |
| `main_script/copyable/public/` | `resources/web/public/` | Assets |
| `main_script/include/schema/T4.4.sql` | `database/migrations/*` | Migrations |
| Legacy Travian modules | `app/_travian/` | Transitional module layer |
