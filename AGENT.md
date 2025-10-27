

# Travian T4.6 → Laravel 12 Complete Migration Plan

### Scope
- Backend PHP services located under `app/`, `_travian/`, `services/`, and `TaskWorker/` (legacy Laravel code previously stored in `backend/` now lives in the root `app/` directory).
- Frontend Angular assets in `angularIndex/` and accompanying static entry points (`index.php`, `docs/`, `resources/`).
- Database assets defined in `database/`, `main.sql`, and incremental migrations under `scripts/` and `sections/`.
- Infrastructure and automation scripts within `main_script`, `main_script_dev`, and `scripts/`.

## Project Overview

Migrate the legacy TravianT4.6 browser game from a custom PHP 7.3-7.4 framework to Laravel 12 with Livewire 3, using the existing `/backend` Laravel auth foundation as the base and merging all old Travian game mechanics.

### Deliverables
1. Fully migrated codebase with updated directory structure and namespaces.
2. Database schema aligned with the normalized redesign described below.
3. Comprehensive automated test suite with integration coverage for migration-critical flows.
4. Deployment and rollback runbooks updated to reflect new infrastructure.
5. Stakeholder-approved Laravel 12 migration plan (see `travian-to.plan.md`).

**Key Statistics:**

- **Old Codebase:** 90 database tables, 45 models, 80+ controllers, 200+ templates, custom MVC framework
- **Backend Laravel:** Auth system with Fortify, 8 migrations, 7 game models, sitter system, multi-account detection
- **Target:** Unified Laravel 12 application in project root with pure Livewire architecture

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

### Components
1. **API Gateway**: Kubernetes ingress (Envoy-based) routing to context-specific APIs.
2. **Accounts Service**: Handles authentication, authorization, and profile management.
3. **Economy Service**: Manages resource production, storage, and trades with ledger integration.
4. **Warfare Service**: Processes troop movements, battles, and generates battle reports.
5. **Communication Service**: Manages messaging, notifications, and event subscriptions.
6. **Alliance Service**: Coordinates alliance formation, membership, and governance.
7. **Event Processor**: Consumes `event_queue` to orchestrate asynchronous game events.
8. **Data Sync Service**: Handles ETL flows to analytics and data warehouse targets.

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
- Baseline `villages` schema now lives in `database/migrations/2025_12_05_000100_create_villages_table.php` (id, world_id, user_id, x, y, is_capital, population, loyalty, culture_points) with enforced FK links to `worlds` and `users`.

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

- Authentication flow
- Village creation
- Building upgrade
- Troop training
- Attack sending and resolution
- Market trades
- Alliance management
- Hero adventures

### 11.3 Integration Tests

- Queue job processing
- Real-time resource updates
- Concurrent attacks
- Session management
- Multi-account detection

## Phase 12: Deployment & Cutover

### 12.1 Environment Setup

- Nginx configuration (point to Laravel public/)
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
3. Switch Nginx to Laravel public/
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

## File Mapping Reference

| Old Path | New Path | Type |
|----------|----------|------|
| `backend/*` | `/` (root) | Laravel Base |
| `main_script/include/Model/PlayerModel.php` | `app/Models/User.php` | Model |
| `main_script/include/Model/VillageModel.php` | `app/Models/Village.php` | Model |
| `main_script/include/Controller/Dorf1Ctrl.php` | `app/Livewire/Village/Overview.php` | Livewire |
| `main_script/include/Game/BattleCalculator.php` | `app/Services/BattleService.php` | Service |
| `main_script/include/resources/Templates/` | `resources/views/` | Views |
| `main_script/copyable/public/` | `public/` | Assets |
| `main_script/include/schema/T4.4.sql` | `database/migrations/*` | Migrations |
| Old Travian files | `/_travian/` | Archive |
