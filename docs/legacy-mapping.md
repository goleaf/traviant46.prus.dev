# Legacy Travian Directory → Laravel Destination Map

This table documents how each legacy `/_travian` path is being decomposed into the modern Laravel
application layers.  Columns correspond to the Laravel domains where the functionality now lives.

| Legacy path | Domain destinations | Actions destinations | Livewire destinations | Console/Jobs destinations | Database destinations | Rationale |
| --- | --- | --- | --- | --- | --- | --- |
| `/_travian/angularIndex/` | — | — | `resources/views/layouts/app.blade.php`<br>`resources/views/livewire/*` | — | — | The Angular bootstrap shell is replaced by Blade layouts and Livewire views rendered server-side for faster initial loads and unified theming. |
| `/_travian/config/` | `config/game.php`<br>`config/travian/*.php`<br>`app/Support/Travian/LegacyConfigRepository.php` | — | — | — | — | Environment configuration is now expressed through Laravel config files resolved by the container instead of runtime includes. |
| `/_travian/controllers/` | `app/Services/Game/*` | `app/Actions/*` | `app/Livewire/*`<br>`routes/web.php` | — | — | Controllers become thin Livewire components backed by explicit actions and services so routing, validation, and middleware leverage Laravel facilities. |
| `/_travian/core/` | `app/Services/Game/*`<br>`app/Support/*` | — | — | `app/Jobs/*`<br>`app/Console/Kernel.php` | — | Engine helpers and bootstrap scripts are moved into testable services and queued jobs orchestrated by the scheduler. |
| `/_travian/dbbackup.php` | — | — | — | `app/Jobs/BackupDatabase.php`<br>`app/Console/Kernel.php` | `database/seeders/BackupLogSeeder.php` (audit trail) | Database backups run as queued jobs scheduled in Artisan, with seeding scripts maintaining audit metadata instead of ad-hoc PHP. |
| `/_travian/filtering/` | `app/Support/Security/*` | `app/Actions/Security/*` | — | — | `database/migrations/*_create_moderation_tables.php`<br>`database/seeders/ModerationSeeders.php` | Legacy profanity and URL filters become structured moderation tables with actions and services managing updates and lookups. |
| `/_travian/legacy/` | `app/Support/Travian/*`<br>`docs/runbooks/*` | — | — | — | — | One-off utilities and operational notes are distilled into support helpers and documented runbooks rather than scattered includes. |
| `/_travian/mailNotify/` | — | `app/Actions/Notifications/*` | — | `app/Jobs/SendAuthEventNotification.php` | — | Notification flows migrate to Laravel notification actions backed by queued jobs to standardise delivery and retries. |
| `/_travian/main.sql` | — | — | — | — | `database/migrations/*`<br>`database/seeders/WorldSeeder.php`<br>`database/seeders/GameWorldSeeder.php` | The monolithic SQL dump is decomposed into incremental migrations plus seeders that recreate canonical worlds and populate showcase villages. |
| `/_travian/main_script/` | `app/Models/*`<br>`app/Services/Game/*` | `app/Actions/Game/*` | `app/Livewire/Game/*` | `app/Jobs/Game/*` | `database/factories/*`<br>`database/migrations/*` | The primary game engine splits into cohesive Laravel layers—models, services, actions, jobs, and data factories—to support testing and maintenance. |
| `/_travian/main_script_dev/` | `app/Support/Travian/LegacyExamples.php` | — | — | — | — | Development-only variants survive as documented examples within support helpers for reference without polluting production code. |
| `/_travian/Manager/` | — | — | — | `app/Console/Commands/GameEngineCommand.php`<br>`app/Console/Commands/ProcessServerTasksCommand.php` | — | Shell-based orchestrators convert to first-class Artisan commands that operations can schedule and monitor. |
| `/_travian/models/` | `app/Models/*`<br>`app/ValueObjects/*`<br>`app/Enums/*` | — | — | — | — | Domain entities become strongly typed Eloquent models augmented with value objects and enums for clarity and type safety. |
| `/_travian/public/` | — | — | `public/`<br>`resources/css/app.css`<br>`resources/js/app.js` | — | — | Static assets are processed through Vite and Tailwind while game art is published in Laravel's `public/` directory for cacheable delivery. |
| `/_travian/schema/` | `app/Support/Schema/*` | — | — | — | `database/migrations/*`<br>`docs/database/*` | Schema references inform migration classes and documentation instead of being interpreted dynamically. |
| `/_travian/sections/` | `app/Services/Api/*` | `app/Actions/Api/*` | — | — | — | Section-specific endpoints consolidate into service-backed API actions exposed via Laravel routing and policies. |
| `/_travian/services/` | `app/Services/Game/*`<br>`app/Support/*` | `app/Actions/Game/*` | — | — | — | Service singletons decompose into injectable services and discrete action classes to clarify responsibilities. |
| `/_travian/TaskWorker/` | — | — | — | `app/Jobs/*`<br>`routes/console.php` | — | Task worker scripts become queue jobs registered in the console kernel, eliminating bespoke daemons. |
| `/_travian/views/` | — | — | `resources/views/livewire/*`<br>`resources/views/layouts/*` | — | — | PHP templates translate to Blade partials rendered by Livewire components for consistent rendering and state management. |
| `/_travian/README.md` | `docs/README.md` | — | — | — | — | High-level project notes integrate into the Laravel documentation set to keep a single canonical knowledge base. |
| `/_travian/CONTRIBUTING.md` | `docs/CONTRIBUTING.md` | — | — | — | — | Contribution guidance is merged with the main docs so contributors follow one process. |
| `/_travian/LICENSE` | `LICENSE` | — | — | — | — | Licensing remains at the repository root to apply uniformly to the migrated codebase. |

