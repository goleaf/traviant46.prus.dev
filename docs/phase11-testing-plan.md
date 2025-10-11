# Phase 11 Testing Strategy

## Overview
Phase 11 focuses on establishing high-confidence automated and manual test coverage ahead of the migration cutover. The objectives are to validate the functional parity of migrated services, surface regression risks early, and ensure observability around asynchronous workflows. This plan aligns testing deliverables with the modular architecture introduced in earlier phases and provides guidance for tooling, data management, and reporting.

## 11.1 Unit Tests

### Game Formulas
- **Battle Calculator**:
  - Cover deterministic combat outcomes for all troop archetypes, siege equipment, and hero modifiers using table-driven PHPUnit tests.
  - Validate casualty rounding rules, morale calculations, and wall/fortification effects with edge-case fixtures.
  - Introduce property-based tests (via `infection/laravel-testing-tools` or an equivalent library) to fuzz input permutations for probabilistic combat components.
- **Production**:
  - Mock resource tick services to verify production scaling across building levels, oasis bonuses, artefacts, and alliance buffs.
  - Assert production caps enforce warehouse/granary limits and trigger overflow events.
- **Speed**:
  - Unit test travel time calculators covering base speed, terrain modifiers, hero equipment, and alliance boosts.
  - Validate rounding behaviour for fractional travel times to align with legacy UI expectations.

### Services
- **Building Service**:
  - Test building queue scheduling, prerequisite checks, and cancellation logic using service container mocks.
  - Assert resource deductions and refund scenarios integrate with the ledger service contracts.
- **Training Service**:
  - Verify troop queue creation, batching rules, and hero training edge cases.
  - Mock production boosts and speed artefacts to confirm time reductions are applied correctly.
- **Movement Service**:
  - Ensure outbound and inbound movements respect population limits, rally point slots, and alliance restrictions.
  - Validate scouting, reinforcement, and attack flows emit the correct domain events.

### Models
- **Relationships**:
  - Add PHPUnit tests to confirm Eloquent relationship definitions (hasMany, belongsToMany, morph relations) match the normalized schema.
  - Ensure pivot tables expose expected accessors (`withPivot`) for troop stacks, alliance roles, and market trades.
- **Scopes**:
  - Test global and local scopes (e.g., active players, ongoing adventures) to prevent unintended query filters.
- **Accessors/Mutators**:
  - Validate derived attributes for production rates, hero stats, and village coordinates maintain backwards compatibility with legacy API consumers.

## 11.2 Feature Tests

- **Authentication Flow**:
  - Implement Laravel feature tests covering registration, login, two-factor authentication, and password reset with throttling assertions.
  - Use database transactions with sanitized fixtures to mirror real-world player setup.
- **Village Creation**:
  - Test the onboarding flow from world selection to initial village provisioning, including starter quests and resource initialization.
- **Building Upgrade**:
  - Simulate full-stack upgrade interactions, verifying queue entries, resource deductions, and WebSocket broadcasts for progress updates.
- **Troop Training**:
  - Cover normal training, hero recruitment, and cancellation paths; assert queue processing jobs are dispatched.
- **Attack Sending and Resolution**:
  - Use API-level tests to launch raids, full assaults, and catapult strikes; confirm battle reports persist and notifications emit.
- **Market Trades**:
  - Test creation, acceptance, and cancellation of marketplace offers with trade tax enforcement and alliance-only restrictions.
- **Alliance Management**:
  - Validate invitation, acceptance, promotion/demotion, and removal flows respecting role-based permissions.
- **Hero Adventures**:
  - Ensure adventure generation, embarkation, completion rewards, and hero health deductions operate within expected thresholds.

## 11.3 Integration Tests

- **Queue Job Processing**:
  - Execute integration suites using Laravel's queue fakes and Redis-backed workers to validate job chaining, retries, and failure handling.
  - Instrument Horizon metrics assertions to confirm throughput under load-testing scenarios.
- **Real-Time Resource Updates**:
  - Run browser-based or API-driven tests that simulate concurrent resource ticks and verify broadcasting to subscribed clients via Pusher or Laravel Echo Server.
- **Concurrent Attacks**:
  - Use database transactions and queued jobs to simulate simultaneous attacks on the same target; assert locking mechanisms prevent race conditions and duplicate battle reports.
- **Session Management**:
  - Validate session persistence across devices, inactivity timeouts, and forced logout during security events using Redis/Database session stores.
- **Multi-Account Detection**:
  - Integrate IP/device fingerprint fixtures to ensure detection services trigger review flags without impacting legitimate players sharing networks.

## Tooling & Reporting
- Standardize on PHPUnit 10 for unit and feature tests, Pest for expressive syntax where beneficial, and Laravel Dusk/Cypress for browser-based scenarios.
- Leverage GitHub Actions workflows to run the test matrix (unit, feature, integration) with artefact uploads for failed screenshots and logs.
- Publish daily coverage reports to Codecov; gate merges on 80% minimum backend coverage and 70% feature test coverage.
- Configure Slack/Teams webhooks for nightly build notifications and test regressions.

## Test Data Management
- Maintain seeders and factories in `database/seeders` and `database/factories` with environment-specific overrides for staging and performance environments.
- Use sanitized production snapshots for integration tests, ensuring PII is anonymized per security guidelines.
- Implement versioned fixture sets to keep test data aligned with schema migrations.

## Exit Criteria
- All Phase 11 test suites green for three consecutive nightly runs.
- No open severity-1 or severity-2 defects related to gameplay, economy, or authentication.
- Observability dashboards (Horizon, Prometheus, Grafana) show stable latency and error rates during concurrent test execution windows.
- QA, SRE, and Product stakeholders sign off on the readiness checklist documented in `docs/migration-assessment.md`.

