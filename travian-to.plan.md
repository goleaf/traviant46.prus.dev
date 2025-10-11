# Travian T4.6 → Laravel 11 Complete Migration Plan
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
- Nginx web server
- MariaDB/MySQL database
- Process supervision: systemd for web/mail services, Supervisor for Laravel queue workers

### Target State (Detailed Requirements)

**Framework:**
- Laravel 11.x (latest stable)
- PHP 8.2+ (ideally 8.3 for performance)
- Composer 2.x for dependency management

**Frontend Stack:**
- Livewire 3.x for reactivity
- Flux UI library for components
- Alpine.js (bundled with Livewire)
- Vite for asset compilation
- Tailwind CSS (if Flux UI uses it)

**Backend Stack:**
- Eloquent ORM for database
- Laravel Queue (Redis driver)
- Laravel Fortify for authentication
- Laravel Sanctum for API tokens (if needed)
- Laravel Horizon for queue monitoring
- Spatie packages for permissions/activity log (if needed)

**Database:**
- MariaDB 10.6+ or MySQL 8.0+
- Redesigned schema following Laravel conventions
- Proper foreign key constraints
- Indexed columns for performance
- JSON columns for flexible data

**Infrastructure:**
- Nginx (keep existing)
- Supervisor for queue workers (replace SystemD services)
- Laravel Octane (optional, for performance boost)
- Redis 6.0+ for cache/queue/sessions

---

## Phase 1: Foundation & Setup (Week 1-2)

### 1.1 Laravel Installation

- Install Laravel 11 in new directory: `/www/wwwroot/traviant46.prus.dev/laravel-app`
- Configure `.env` with existing database credentials
- Install dependencies: Livewire 3, Flux UI, Laravel Debugbar
- Set up multi-language support (laravel-translatable or similar)
- Configure Redis for cache/sessions/queues

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
    - Fold into `email_verifications` (shared with activation above) with status enum (`pending`, `completed`, `expired`).
    - Add `metadata` JSON column to capture IP, user agent, locale for security checks.
    - Convert UNIX `time` column to TIMESTAMP; add automatic pruning job using `expires_at`.

- **`deleting`**
  - *Purpose:* Schedules account deletions with countdown timers.
  - *Issues:* Uses `uid` without FK, stores `timestamp` integers, lacks reason tracking.
  - *Redesign:*
    - Rename to `account_deletions` with `user_id`, `initiated_at`, `scheduled_for`, `cancelled_at`, `reason`, `initiated_by_admin_id`.
    - Add FK to `users.id` and `users` alias for admin actions.
    - Provide soft delete column to track cancellations.

- **`newproc`**
  - *Purpose:* Flags users currently going through tutorial/activation processes.
  - *Issues:* Essentially a status table with `uid`, `stage`, `started` integers.
  - *Redesign:*
    - Merge into `user_onboarding_states` with `user_id`, `current_stage`, `step_data` (JSON), `started_at`, `completed_at`.
    - Replace game checks with Eloquent relationships and events.
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

- Install Laravel Fortify for authentication
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

- Update nginx configuration
- Roll out Supervisor-managed queue workers and scheduler processes
- Laravel Octane (optional for performance)
- Ensure Redis 6+ is provisioned for cache/queue/session drivers

### 10.3 Cutover Plan

1. Announce maintenance window
2. Stop old automation engine
3. Migrate final data snapshot
4. Switch nginx to Laravel app
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
| **Team unfamiliarity with Laravel 11** | Medium | Medium | Formal Livewire & Horizon workshops, pair migration sessions, internal wiki for patterns. |
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
