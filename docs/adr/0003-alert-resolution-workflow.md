# ADR 0003: Multi-Account Alert Resolution Workflow

- **Date:** 2025-02-14
- **Status:** Accepted
- **Decision Makers:** Security Operations WG
- **Related Code:** `app/Services/Security/MultiAccountDetector.php`, `app/Models/MultiAccountAlert.php`

## Context

The platform raises security alerts whenever multiple accounts authenticate from the same IP address. The legacy system wrote flat log files, forcing moderators to manually cross-reference events and making it impossible to resolve an alert once actioned.

## Decision

Persist multi-account alerts in the `multi_account_alerts` table with a hashed `group_key`, `user_ids` JSON payload, and timestamps for `first_seen_at` / `last_seen_at`. Each alert groups all accounts seen on the same IP within the monitored window and stores an aggregate `severity`. Resolution metadata lives in a separate moderation table so that alerts can be referenced historically even after closure.

## Consequences

- **Positive:** Provides a single source of truth for open security incidents with full audit history through immutable alert records.
- **Positive:** Enables automatic dashboard counts and SLA tracking for the moderation team by querying on `severity` and `last_seen_at`.
- **Negative:** Requires moderation tooling to maintain a join table for resolution state until we extend the schema with native fields.
- **Considerations:** When evolving the design, prefer appending columns (e.g. `resolved_at`) rather than mutating existing keys so the `group_key` remains stable across deployments.
