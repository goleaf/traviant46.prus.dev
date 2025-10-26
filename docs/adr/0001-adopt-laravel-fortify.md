# ADR 0001: Adopt Laravel Fortify for Authentication

- **Date:** 2025-02-14
- **Status:** Accepted
- **Decision Makers:** Platform Architecture Group
- **Related Issues:** Migration roadmap `travian-to.plan.md` sections 120-185

## Context

The legacy TravianT authentication stack lived in `_travian` with bespoke session handling, weak password hashing, and limited support for modern security requirements (2FA, email verification, and delegated access). The new Laravel service needed a first-class authentication backend that integrates with Redis for sessions and supports automated testing, rate limiting, and guard customisation.

## Decision

Adopt [Laravel Fortify](https://laravel.com/docs/fortify) as the authentication and registration backend. Fortify provides battle-tested features for login, password resets, two-factor authentication, and email verification while keeping the UI layer inside the application (Blade + Livewire).

## Consequences

- **Positive:** Reduced maintenance cost by relying on a core Laravel package with security fixes delivered upstream. Easy integration with Laravel rate limiting, password hashing, and guard customisation.
- **Positive:** Enables incremental replacement of legacy PHP handlers while preserving compatibility with existing session cookies via custom responses.
- **Negative:** Requires adapting legacy clients to the Fortify endpoints and responses, which introduces short-term integration work.
- **Considerations:** Fortify is headless; front-end components must be maintained in Livewire. When building new flows, prefer extending Fortify actions over writing bespoke controllers.
