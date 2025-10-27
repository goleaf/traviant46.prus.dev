# Alerting Lifecycle

TravianT raises security alerts when suspicious login activity is detected, primarily focusing on shared IP usage and sitter behaviour. This document captures the lifecycle from generation to resolution.

## 1. Signal Ingestion

1. **Authentication event** — Every successful login triggers `LoginActivity::create()`. The record stores `user_id`, optional `acting_sitter_id`, `ip_address`, and `via_sitter`.
2. **Detector invocation** — `App\Services\Security\MultiAccountDetector::record()` runs within the Fortify success pipeline. It loads distinct user IDs that previously authenticated from the same IP.
3. **Alert aggregation** — For each conflicting user set, the detector calls `touchAlert()`, which upserts a `MultiAccountAlert` using a stable `group_key` and JSON `user_ids` payload. The first sighting sets `first_seen_at`; subsequent sightings update `last_seen_at`.

## 2. Alert States

`multi_account_alerts` captures immutable event aggregates:

| Field | Purpose |
|-------|---------|
| `alert_id` | UUID for external references (support tickets, logs). |
| `group_key` | Deterministic hash of sorted `user_ids` + `ip_address`; ensures idempotency. |
| `user_ids` | JSON array of all players involved in the conflict. |
| `ip_address` | Normalised IPv4/IPv6 string, nullable when detection happens without IP. |
| `severity` | `low`, `medium`, `high`, chosen by detector heuristics (default `low`). |
| `first_seen_at` / `last_seen_at` | Track alert freshness for dashboards and escalations. |

Moderation tooling layers a **resolution state** on top of alerts inside the staff dashboard. Each resolution entry references the `alert_id`, the staff member responsible, and a free-form note. Alerts are considered **open** when no resolution entry exists or the most recent entry leaves the alert in a pending state.

## 3. Moderator Workflow

1. **Queue** — Moderators sort alerts by severity descending, then `last_seen_at`. `recent()` scope surfaces the past 14 days by default.
2. **Review** — The moderation UI joins `LoginActivity` to surface the raw timeline (who logged in, sitter context, IP drift).
3. **Action** — Depending on investigation, moderators:
   - Issue temporary bans or warnings.
   - Remove sitter permissions.
   - Dismiss the alert when behaviour is legitimate.
4. **Resolution** — The action is captured as a moderation event (close / uphold). Future logins on the same IP automatically generate a new alert (fresh `alert_id`) so that repeat offenders resurface in the queue even after prior dismissal.

### Admin Dashboard Component

- The Livewire component `App\Livewire\Admin\Alerts` powers the `/admin/multi-account-alerts` view. It queries the existing authentication service tables directly via Eloquent and paginates the latest alerts.
- Staff can filter by severity, status, world, source type, IP, device hash, or a fuzzy search term. Filter state persists in the query string so investigations can be shared via URL.
- Resolve and dismiss actions call `MultiAccountAlertsService` to update the alert status and capture an audit note tied to the acting administrator. Notes are trimmed, validated (1,000 characters max), and surfaced alongside the alert history.
- Successful actions broadcast an `admin.alerts:refresh` event so collaborating moderators see state changes immediately.

## 4. Auto-Remediation & Cleanup

- **Stale Alerts:** A nightly job flags alerts older than 90 days without activity; the moderation UI hides them unless explicitly searched.
- **Reporting:** Weekly analytics summarise total alerts, resolution times, and SIT (sitter-involved) ratios for leadership review.
- **Data Retention:** Alerts align with GDPR policy—retained for 18 months before anonymisation scripts redact IPs.

## 5. Integrations

- **Webhooks:** Security audit service subscribes to alert creations via internal event dispatchers (`AlertRaised`), triggering Slack and PagerDuty notifications for `high` severity incidents.
- **Support Tools:** Customer care portal uses `alert_id` to link user tickets back to the original alert and resolution note, improving transparency.

## References

- [`App\Services\Security\MultiAccountDetector`](../app/Services/Security/MultiAccountDetector.php)
- [`App\Models\LoginActivity`](../app/Models/LoginActivity.php)
- [`App\Models\MultiAccountAlert`](../app/Models/MultiAccountAlert.php)
- [ADR-0003: Multi-Account Alert Resolution Workflow](adr/0003-alert-resolution-workflow.md)
