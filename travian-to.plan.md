# Travian T4.6 → Laravel 11 Complete Migration Plan

## Project Analysis Summary

**Current State:**

- Custom MVC framework (non-standard)
- PHP 7.3-7.4 requirement
- ~45 Model files, ~80+ Controller files, extensive template system
- Custom autoloader, custom database wrapper (mysqli)
- Redis caching, sessions
- Systemd services for game automation engine
- Custom job system with pcntl_fork for background workers
- ~70+ database tables (estimated from schema)
- Template-based views (PHP files)
- Multi-language support (Persian, Arabic, English, Greek)
- Complex game mechanics: buildings, troops, battles, artifacts, heroes, alliances

**Target State:**

- Laravel 11 (latest version)
- PHP 8.2+
- Livewire 3 + Flux UI
- Eloquent ORM with redesigned schema
- Laravel Queue system (database/Redis driver)
- Modern authentication (Laravel Breeze/Fortify)
- Blade components
- Service-oriented architecture

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

6. **Routine Jobs:**

- `App\Jobs\ProcessAdventures`
- `App\Jobs\ProcessAuctions`
- `App\Jobs\CleanupInactivePlayers`
- `App\Jobs\BackupDatabase`

### 4.2 Scheduled Commands

- `app/Console/Commands/GameEngineCommand.php` - main game loop
- Schedule in `Kernel.php` with appropriate frequencies
- Health monitoring and logging

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
- Supervisor for queue workers
- Laravel Octane (optional for performance)
- Update systemd services

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