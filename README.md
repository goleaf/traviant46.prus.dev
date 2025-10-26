# TravianT Authentication Platform

Laravel 12 service that powers authentication, sitter delegation, and security alerting for TravianT. The application modernises the legacy PHP stack while maintaining compatibility with existing game services.

## Architecture

```mermaid
graph TD
    subgraph Clients
        Web[Web UI (Livewire)]
        Legacy[Legacy Travian client]
        APIs[3rd-party tools]
    end

    subgraph Laravel
        Fortify[Fortify Auth]
        Controllers[HTTP Controllers & Livewire]
        Services[Domain Services]
        Jobs[Queue Jobs]
    end

    subgraph Data
        MySQL[(MySQL)]
        Redis[(Redis)]
        Logs[(Audit Trail)]
    end

    Web --> Fortify
    Legacy --> Fortify
    APIs --> Controllers

    Fortify --> Controllers
    Controllers --> Services
    Services --> MySQL
    Services --> Redis
    Services --> Logs
    Jobs --> Redis
    Jobs --> MySQL

    Redis -. session, cache .- Fortify
    Logs -. login_activity .- Services
```

- **Fortify** handles credential authentication, password resets, and email verification.
- **Livewire** components render the secure dashboard, sitter tooling, and moderation views.
- **Redis** stores sessions, rate limits, and sitter invitation handshakes.
- **MySQL** persists canonical data: users, sitter assignments, login activities, and multi-account alerts.
- **Domain services** such as `MultiAccountDetector` and the `SitterDelegation` model enforce business rules shared with legacy infrastructure.

## Core Flows

### Authentication

```mermaid
sequenceDiagram
    participant Client
    participant Fortify as Fortify Actions
    participant AuthSrv as Auth Service
    participant Redis
    participant Detector as MultiAccountDetector
    participant MySQL

    Client->>Fortify: POST /login (username/email, password)
    Fortify->>MySQL: Validate credentials
    Fortify->>AuthSrv: SuccessfulLoginResponse
    AuthSrv->>Redis: Issue session (guard web)
    AuthSrv->>MySQL: Record LoginActivity
    AuthSrv->>Detector: record(user, ip, timestamp, via_sitter?)
    Detector->>MySQL: upsert MultiAccountAlert records
    AuthSrv-->>Client: Authenticated session cookie
```

- Passwordless sessions such as sitter delegation flag `via_sitter` and store the acting sitter ID.
- Failed attempts are throttled using Redis-backed Fortify rate limiting.
- Post-auth checks redirect to verification, maintenance, or ban Livewire screens depending on state.

### Sitter Delegation

```mermaid
sequenceDiagram
    participant Owner as Account Owner
    participant API as /sitters API
    participant MySQL
    participant Redis

    Owner->>API: POST /sitters { sitter_username, permissions[], expires_at? }
    API->>MySQL: upsert sitter_assignments
    API-->>Owner: 201 Created + assignment payload
    Note over API,Redis: Sessions include sitter context when acting_sitter_id is present
    Owner->>API: GET /sitters
    API->>Redis: Read acting sitter context
    API->>MySQL: Fetch active assignments
    API-->>Owner: List with permissions & expiry
    Owner->>API: DELETE /sitters/{sitter}
    API->>MySQL: detach relationship, delete assignment
    API-->>Owner: 204 No Content
```

- Owners cannot self-assign and must reference an existing user by username.
- Permissions are stored as JSON for compatibility with legacy sitter rules.
- Expired assignments are filtered with the `active()` scope on `SitterAssignment`.

### Alert Lifecycle

```mermaid
sequenceDiagram
    participant Login as LoginActivity
    participant Detector as MultiAccountDetector
    participant Alerts as MultiAccountAlert
    participant Moderation as Moderation UI

    Login->>Detector: record(user, ip, timestamp)
    Detector->>Alerts: firstOrNew(primary, conflict, ip)
    Alerts->>Alerts: increment occurrences, stamp last_seen_at
    Moderation->>Alerts: Poll recent alerts
    Moderation-->>Moderator: Display unresolved conflicts
    Moderator->>Alerts: mark resolved (model flag or dismissal)
    Alerts-->>Moderation: Removed from active queue
```

- Every successful login (direct or via sitter) records an activity row.
- Detector cross-references prior logins sharing an IP to create bidirectional alerts.
- Moderation tooling marks an alert resolved by setting application-level status (see ADR-0003).

## Documentation Index

- [ADR catalogue](docs/adr/README.md)
- [Sitter API reference](docs/sitter-api.md)
- [Alerting lifecycle](docs/alerting-lifecycle.md)
- [On-call runbooks](docs/runbooks/README.md)
- [Legacy migration plan](docs/project-analysis.md)

## Local Development

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
```

Run targeted tests with `php artisan test --filter=Sitter` and lint PHP with `vendor/bin/pint --dirty`.

---

Released under the MIT License.
