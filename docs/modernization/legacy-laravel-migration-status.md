# Legacy-to-Laravel Migration Status

The Travian modernization effort is currently split between the legacy PHP engine and the new Laravel 12 stack. Use the notes below as a quick reference when planning or implementing migration work.

## Legacy Engine Footprint
- Production gameplay continues to run from `main_script/include/`, leveraging the bespoke autoloader, controller tree, and helper utilities that predate the Laravel codebase.
- Core gameplay behaviors—including troop calculations, adventure scheduling, and maintenance/cron tasks—remain in the legacy include tree. Modernization projects that need these behaviors must call back into the legacy helpers.

## Laravel 12 Backend
- A fully bootstrapped Laravel 12 application lives under `backend/` with Fortify authentication, queue workers, Horizon monitoring, and standard Artisan tooling already configured.
- New APIs, background jobs, and database migrations should target this application so that gameplay logic can gradually leave the legacy include tree.

## Livewire Game Client Components
- Livewire components now power the modern UI, using the `BuildingComponent` base class as the pattern for handling village and world interactions.
- Additional components should follow this example by preferring Eloquent models and Laravel policies instead of accessing global legacy state directly.

## Authentication Bridge Services
- Bridge services such as `LegacyLoginService` translate sitter permissions, activation tokens, and legacy password hashing into Laravel-friendly flows.
- Any new authentication, registration, or session management work should integrate with these bridges to preserve legacy edge cases.

## Immediate Focus Areas
- Identify high-traffic legacy controllers that can be proxied through Laravel routes so Livewire or Blade replacements can take over incrementally.
- Expand automated test coverage around the bridge services to guarantee sitter and activation flows continue to match legacy behavior.
- Catalogue outstanding global helpers in `main_script/include/` that still need Laravel facades or service bindings to streamline future migrations.
