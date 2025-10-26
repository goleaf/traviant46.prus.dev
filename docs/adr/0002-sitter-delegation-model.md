# ADR 0002: Persist Sitter Delegations in `sitter_delegations`

- **Date:** 2025-02-14
- **Status:** Accepted
- **Decision Makers:** Platform Architecture Group
- **Related Code:** `app/Models/SitterDelegation.php`, `database/migrations/2025_02_14_000002_create_sitter_delegations_table.php`

## Context

Legacy sitter relationships were stored as comma-delimited columns on the `users` table, causing data integrity issues and making it impossible to track per-sitter permissions, expiry windows, or audit changes. The modern service needs a normalised representation that keeps track of the acting sitter and supports future features like sitter analytics.

## Decision

Introduce a dedicated `sitter_delegations` table with a corresponding `SitterDelegation` Eloquent model. The table uses `(owner_user_id, sitter_user_id)` as a unique pair, stores JSON permissions, and keeps optional expiration timestamps. Relationships are exposed through `User::sitters()` and `User::sitterAssignments()` for convenient eager loading.

## Consequences

- **Positive:** Enables granular permission controls and expiry handling without schema hacks.
- **Positive:** Simplifies API contracts by serialising assignments through the model rather than manual JSON assembly.
- **Negative:** Requires migration scripts to backfill relationships from legacy columns during transitional deployments.
- **Considerations:** Ensure cascading deletes handle account removal and that background jobs prune expired delegations to keep the table lean.
