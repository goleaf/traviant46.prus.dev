# Authentication Layer Migration Plan

This document tracks the remaining work required to migrate the legacy Travian authentication implementation to the new Laravel-powered stack.
The goal is to retire the bespoke controllers and models located under `main_script`/`sections` in favour of Fortify actions, route definitions,
and dedicated service classes.

## Summary of migration targets

| Legacy entry point | New home | Migration themes |
| --- | --- | --- |
| `main_script/include/Controller/LoginCtrl.php` | Fortify authentication actions | Handshake logins, maintenance bypass, captcha, forgotten password flow |
| `sections/api/include/Api/Ctrl/RegisterCtrl.php` | Fortify/Jetstream registration pipeline | Activation handling, validation, newsletter, invitation support |
| `main_script/include/Controller/LogoutCtrl.php` | Laravel logout routes & controllers | Session teardown, low-resolution cookie hand-off |
| `main_script/include/Model/LoginModel.php` | Domain service classes | Login lookup, sitter checks, password reset email dispatch |

The sections below outline how to decompose each legacy artefact and the Laravel building blocks that must replace it.

## `Controller\\LoginCtrl.php` → Fortify actions

### Responsibilities captured in the legacy file

* Performs token-based “handshake” logins used by support/multihunter tooling before rendering the login form. 【F:main_script/include/Controller/LoginCtrl.php†L24-L77】
* Renders the login page (out-of-game view) including recaptcha, low-resolution flag, and success/error states. 【F:main_script/include/Controller/LoginCtrl.php†L84-L134】
* Implements password reset requests (`passwordForgotten`) that call into `LoginModel::findLogin` and `LoginModel::addNewPassword`. 【F:main_script/include/Controller/LoginCtrl.php†L136-L187】
* Authenticates posted credentials, handles sitter login fallbacks, and writes Travian-specific session metadata. 【F:main_script/include/Controller/LoginCtrl.php†L189-L336】

### Migration plan

1. **Fortify `AttemptToAuthenticate` customisation** – translate the “handshake” logic into a Fortify action that runs before the normal credential check. Persist the ability to log in as Support/Multihunter with signed tokens and respect the low-resolution cookie toggle.
2. **Fortify `AuthenticateUser` action** – port the password and sitter login fallbacks so that Fortify can recognise sitter credentials (returning different guard contexts when necessary). Encapsulate the sitter lookups in injectable services described later.
3. **Fortify `PrepareAuthenticatedSession` hook** – move the maintenance-mode bypass, welcome text messaging, and session timer calculations here so that every successful login configures the Travian session with the same metadata the controller currently writes.
4. **Password reset action** – build a custom Fortify password reset pipeline that reuses the mail template from `LoginModel::addNewPassword` and still honours the captcha requirement.
5. **View layer** – replace the PHPBatch view rendering with Blade templates; use Fortify’s view binding hooks to surface the login page copy, captcha toggles, and forgot-password form state.
6. **Validation** – reuse the recaptcha verification in a request validator (Fortify `LoginRateLimiter` or form request) to make sure automated logins still fail when the captcha is required.

## `Controller\\RegisterCtrl.php` → Registration flow

### Responsibilities captured in the legacy file

* Resends activation mails and validates the target world before doing so. 【F:sections/api/include/Api/Ctrl/RegisterCtrl.php†L18-L50】
* Validates activation codes, verifies recaptcha tokens, and issues activation hand-off tokens to world-specific endpoints. 【F:sections/api/include/Api/Ctrl/RegisterCtrl.php†L52-L110】
* Handles new registrations including invitation codes, newsletter subscription, and multiple custom validation rules (username blacklist, email length limits, registration key requirements). 【F:sections/api/include/Api/Ctrl/RegisterCtrl.php†L112-L214】

### Migration plan

1. **Fortify registration feature** – implement a Fortify `CreatesNewUsers` action that mirrors validation rules for usernames, emails, registration keys, inviter payloads, and acceptance of terms/newsletter.
2. **Activation handling** – convert the activation lookup and redirect logic into queued jobs or events that run after user creation, leveraging Laravel’s notification/mail system instead of manual PDO queries.
3. **Captcha integration** – wire the recaptcha validation into the registration request validator (or a dedicated middleware) so that Fortify blocks activation attempts with invalid captchas.
4. **World routing** – introduce configuration-driven world resolution services to replace the direct `Server::getServerById` calls. This keeps Fortify decoupled from the existing database schema while preserving multi-world support.
5. **API compatibility** – expose the registration endpoints through Laravel API routes, ensuring JSON payloads and response shapes stay backward-compatible for any clients still using the old `/api/register` endpoints during the transition.

## `Controller\\LogoutCtrl.php` → Logout routes

### Responsibilities captured in the legacy file

* Guards the route so that only authenticated Travian sessions hit the logout view; otherwise users are redirected to the login controller. 【F:main_script/include/Controller/LogoutCtrl.php†L10-L17】
* Renders a logout confirmation page containing username, timestamp, and the low-resolution flag; then destroys the session. 【F:main_script/include/Controller/LogoutCtrl.php†L18-L27】

### Migration plan

1. **Route definition** – create an authenticated Laravel route (`POST /logout` + optional `GET /logout`) that calls Fortify’s logout handler but still renders the confirmation view when accessed over GET.
2. **Session teardown** – ensure the logout action clears cookies (including `lowRes`) and terminates Travian session flags exactly as `Session::getInstance()->logout()` currently does.
3. **Blade view** – replicate the confirmation page as a Blade template while keeping localisation strings intact.
4. **Redirect policy** – retain the redirect-to-login behaviour when the user is no longer authenticated, ideally through Laravel’s `auth` middleware.

## `Model\\LoginModel.php` → Service classes

### Responsibilities captured in the legacy file

* Locates login candidates by username/email across multiple tables (users, activation, global activation). 【F:main_script/include/Model/LoginModel.php†L11-L43】
* Issues password reset tokens, writes `newproc` rows, and emails the temporary credentials. 【F:main_script/include/Model/LoginModel.php†L44-L64】
* Supports sitter logins by pulling alternate passwords for delegated accounts. 【F:main_script/include/Model/LoginModel.php†L66-L111】

### Migration plan

1. **`UserLookupService`** – encapsulate the multi-source user search logic so that Fortify authentication can inject it. It should expose typed results differentiating between normal accounts, pending activations, and cross-world activations.
2. **`PasswordResetService`** – move password reset token creation, email composition, and link building into a dedicated service that leverages Laravel’s mailables/notifications rather than manual queries.
3. **`SitterAuthenticationService`** – isolate sitter password matching so that both Fortify login and any background checks can reuse the logic without relying on static methods.
4. **Testing** – cover the services with unit tests (for example, using Pest/PHPUnit) to ensure the multi-branch lookup and sitter fallbacks keep working after the migration.
5. **Dependency inversion** – register the new services in the container and refactor Fortify actions/controllers to depend on the abstractions instead of calling raw DB queries.

## Next steps

* Build a feature flag or environment toggle to switch between the legacy login/register flows and the new Fortify-powered implementation.
* Schedule end-to-end tests (browser + API) that exercise login, logout, registration, activation, and password reset flows to verify parity.
* Coordinate a data migration plan for any tables that Fortify will need (e.g., password resets, personal access tokens) to avoid downtime during the switchover.
