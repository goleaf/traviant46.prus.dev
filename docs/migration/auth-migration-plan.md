# Fortify Authentication Migration Plan

This playbook documents how to migrate the legacy Travian authentication stack into Laravel Fortify. It expands on the high-level backend stack overview and consolidates the scattered references in the documentation so future updates have a single source of truth.

## Objectives
- Recreate the legacy login, registration, and recovery flows using Laravel Fortify actions.
- Preserve sitter-aware session handling and special administrator/multihunter guards referenced throughout the backend stack documentation.
- Provide roll-out and rollback guidance so environments can adopt Fortify safely.

## Prerequisites
1. **Laravel Core Installed.** Confirm the Laravel 12 application is bootstrapped and matches the structure described in [`../backend-stack.md`](../backend-stack.md).
2. **Fortify Package Published.** Require `laravel/fortify` via Composer and publish its configuration and view stubs:
   ```bash
   composer require laravel/fortify
   php artisan vendor:publish --provider="Laravel\\Fortify\\FortifyServiceProvider"
   ```
3. **Configuration Baseline.** Enable Fortify inside `config/app.php` (service provider) and `config/fortify.php`. Set `features` to include password resets, email verification, and two-factor authentication if the world requires it.
4. **Database Readiness.** Run migrations that create the users table, password resets, and any Fortify-specific tables. Align column names with the legacy Travian schema (see [`../database/village-tables.md`](../database/village-tables.md) for naming conventions used elsewhere).

## Migration Steps
1. **Map Legacy Credentials.**
   - Import users from the legacy schema, hashing passwords with Laravel's bcrypt/argon drivers.
   - Store legacy identifiers (`legacy_uid`, sitter relationships) on the `users` table. Use guarded casts or dedicated models to retain compatibility with the sitter features called out in [`../README.md`](../README.md).
2. **Configure Fortify Actions.**
   - Implement custom Fortify callbacks in `app/Providers/FortifyServiceProvider.php` for login, registration, password reset, and verification.
   - During login, resolve sitter context and guard selection (administrator vs. multihunter) as noted in [`../backend-stack.md`](../backend-stack.md).
   - Wrap the callbacks in services that emit activity logs so the auditing guidance in [`../backend-stack.md`](../backend-stack.md) remains enforceable.
3. **Session & Guard Setup.**
   - Define guards in `config/auth.php` for `web`, `admin`, and `multihunter`. Ensure Fortify uses the correct guard per request.
   - Configure session drivers (Redis or database) to match the infrastructure requirements documented in [`../queue-system.md`](../queue-system.md) and [`../background-jobs.md`](../background-jobs.md).
4. **View & Livewire Integration.**
   - Publish Fortify views and convert them to Blade/Livewire components so the UI matches the upcoming Livewire plans referenced in [`../legacy-controller-mapping.md`](../legacy-controller-mapping.md).
   - Apply localisation, sitter warnings, and premium feature prompts noted in [`../communication-components.md`](../communication-components.md) and related docs.
5. **Event & Notification Wiring.**
   - Ensure Fortify events trigger the notification flows described in [`../communication-components.md`](../communication-components.md) (e.g., informing alliance leaders when a sitter logs in).
   - Register listeners that dispatch queued jobs (see [`../background-jobs.md`](../background-jobs.md)) for multi-factor delivery or suspicious login monitoring.
6. **Policy & Permission Alignment.**
   - Map legacy permission flags into policies backed by `spatie/laravel-permission` as highlighted in [`../backend-stack.md`](../backend-stack.md).
   - Validate that Fortify's password reset and verification routes respect these policies.

## Testing Checklist
- Cover Fortify actions with feature tests (`php artisan test`) that simulate regular, sitter, administrator, and multihunter logins.
- Verify password recovery, email verification, and logout flows against legacy expectations.
- Confirm that queue-backed notifications fire and complete successfully (reference [`../queue-system.md`](../queue-system.md)).

## Deployment & Rollback
1. **Staged Roll-Out.** Deploy Fortify to a staging environment, enabling feature flags to fall back to the legacy login if needed.
2. **Data Migration Window.** Migrate user credentials during a maintenance window; freeze sitter changes until Fortify sessions are confirmed stable.
3. **Monitoring.** Track authentication metrics, queue failures, and audit logs as described in [`../backend-stack.md`](../backend-stack.md).
4. **Rollback.** If issues arise, revert to the previous guard configuration and restore the legacy session store from backups.

## Reference Materials
- [`../backend-stack.md`](../backend-stack.md) – high-level technology stack and guard overview.
- [`../README.md`](../README.md) – summary of Fortify usage and sitter requirements.
- [`../background-jobs.md`](../background-jobs.md) & [`../queue-system.md`](../queue-system.md) – queue infrastructure used by Fortify notifications.
- [`../legacy-controller-mapping.md`](../legacy-controller-mapping.md) – mapping from legacy authentication controllers to Fortify bindings.

Maintaining this document alongside the other migration references keeps the authentication stack aligned with the rest of the Laravel modernization effort.
