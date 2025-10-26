# Legacy Path Mapping

This reference bridges every archived `/_travian` directory or root-level script to its Laravel 12 successor so that engineers can locate the modern implementation while decommissioning legacy code.

## Mapping Table

| Legacy path | Layer | Laravel destination | Rationale |
| --- | --- | --- | --- |
| `/_travian/angularIndex/` | Livewire | `resources/views/layouts/app.blade.php`<br>`resources/views/livewire/*` | The Angular bootstrap shell is replaced by Livewire-driven layouts and Flux components so the client renders server-side Blade views instead of a static SPA shell. |
| `/_travian/config/` | Domain | `config/game.php`<br>`config/travian/*.php`<br>`app/Support/Travian/LegacyConfigRepository.php` | Environment-specific configuration is now versioned through Laravel config files and resolved via the container instead of being read from PHP arrays at runtime. |
| `/_travian/controllers/` | Livewire | `app/Livewire/*`<br>`app/Http/Controllers`<br>`routes/web.php` | Legacy MVC controllers are rebuilt as Livewire components or Fortify-backed HTTP controllers, letting routing, validation, and middleware live in the Laravel stack. |
| `/_travian/core/` | Console/Jobs | `app/Jobs/*`<br>`app/Services/Game/*`<br>`app/Support/*`<br>`app/Console/Kernel.php` | Cron automation, helper singletons, and engine bootstrapping are reimplemented as queueable jobs and focused services that the scheduler orchestrates. |
| `/_travian/dbbackup.php` | Console/Jobs | `app/Jobs/BackupDatabase.php`<br>`app/Console/Kernel.php` | Database backups run as a queued job on the scheduler rather than a bespoke PHP script. |
| `/_travian/filtering/` | Database **(planned)** | `database/migrations/` (moderation tables) **(planned)**<br>`app/Services/Security/*` | Static bad-word, URL, and username filters will materialise as seeded moderation tables consumed by security services so moderation data can be audited and updated centrally. |
| `/_travian/legacy/` | Domain | `docs/runbooks/*`<br>`app/Support/Travian/*` | One-off utilities and reference snippets live on as operational runbooks or focused support classes, replacing ad-hoc includes. |
| `/_travian/mailNotify/` | Actions | `app/Notifications/*`<br>`app/Jobs/SendAuthEventNotification.php` | Notification flows migrated to Laravel notifications and queued broadcasters instead of inline mail scripts. |
| `/_travian/main_script/` | Domain | `app/Models/*`<br>`app/Services/Game/*`<br>`app/Livewire/*` | The primary game engine, models, and UI flows are being decomposed into Eloquent models, service classes, and Livewire interfaces. |
| `/_travian/main_script_dev/` | Domain | `app/Models/*`<br>`app/Services/Game/*`<br>`app/Livewire/*` | Development variants of the engine follow the same migration path and now serve purely as historical reference material. |
| `/_travian/main.sql` | Database | `database/migrations/*`<br>`database/seeders/GameWorldSeeder.php` | The monolithic SQL dump is expressed as incremental migrations plus seeders that construct canonical demo data. |
| `/_travian/Manager/` | Console/Jobs | `app/Console/Commands/GameEngineCommand.php`<br>`app/Console/Commands/ProcessServerTasksCommand.php` | Shell-based orchestration is replaced by Artisan commands that the scheduler or ops teams can run declaratively. |
| `/_travian/models/` | Domain | `app/Models/*`<br>`app/ValueObjects/*`<br>`app/Enums/*` | Domain entities now live as typed Eloquent models with supporting value objects and enums. |
| `/_travian/public/` | Livewire | `public/`<br>`resources/css/app.css`<br>`resources/js/app.js` | Static assets are rebuilt through Vite and Tailwind, with game art copied into Laravel's public asset pipeline. |
| `/_travian/schema/` | Database | `database/migrations/*`<br>`docs/database/*` | Schema definitions inform the Laravel migrations and accompanying documentation instead of being parsed at runtime. |
| `/_travian/sections/` | Actions | `routes/api.php`<br>`app/Http/Controllers/Api/*`<br>`app/Http/Controllers/Storefront/*` | Section-specific endpoints are consolidated into versioned API controllers and storefront HTTP controllers, aligning with Laravel routing. |
| `/_travian/services/` | Domain & Actions | `app/Services/Game/*`<br>`app/Actions/*`<br>`app/Support/*` | Game mechanics previously hidden in service singletons are split into injectable services and thin action classes. |
| `/_travian/TaskWorker/` | Console/Jobs | `app/Jobs/*`<br>`routes/console.php` | Task worker scripts become queue jobs and scheduled Artisan tasks, removing the need for bespoke daemon runners. |
| `/_travian/views/` | Livewire | `resources/views/livewire/*`<br>`resources/views/layouts/*` | PHP templates translate to Blade view partials that Livewire renders, leveraging Flux UI components. |
