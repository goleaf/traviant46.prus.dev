# Admin & Log Tables Inventory

This document summarizes the eight administrative and logging tables that power enforcement, auditing, and operational tooling in the legacy Travian 4.6 schema. Each section captures the legacy structure plus the gameplay or moderation features that depend on the table so that we can plan an accurate Laravel migration.

## general_log
- **Schema highlights:** Stores auto-increment id, player uid, log type string, LONGTEXT payload, and UNIX timestamp; indexed on `uid`, `type`, and `time` for filtering.【F:main_script/include/schema/T4.4.sql†L129-L143】
- **Usage:** Generic helper `Core\Log::addLog()` inserts rows for user-facing or background log entries, giving developers a catch-all audit trail.【F:main_script/include/Core/Log.php†L4-L9】
- **Migration considerations:** Replace raw UNIX `time` with Laravel `created_at`; ensure `type` becomes an enum or constrained string so we can map to notification classes. Keep text column for unstructured payloads but evaluate JSON conversion.

## admin_log
- **Schema highlights:** Minimal table capturing admin log text, IP string, optional timestamp, and primary key.【F:main_script/include/schema/T4.4.sql†L1727-L1737】
- **Usage:** `AdminLog::addLog()` records every staff action along with session-derived admin identity; listing endpoints query latest entries for dashboards.【F:main_script/include/admin/include/Core/AdminLog.php†L7-L43】
- **Migration considerations:** Promote `time` column to `timestamp` with default `current_timestamp`; add foreign key to `users` (admins) once roles are migrated. Consider IP normalization to integer and add indexing for time-based pruning.

## log_ip
- **Schema highlights:** Tracks distinct player login IPs with integer storage (`ip` BIGINT), keyed by `uid`/`time`/`ip` for deduplication.【F:main_script/include/schema/T4.4.sql†L56-L66】
- **Usage:** `Core\Helper\IPTracker::addCurrentIP()` maintains the table on login; multi-account detection queries compare IP overlaps and update activity windows.【F:main_script/include/Core/Helper/IPTracker.php†L8-L64】【F:main_script/include/Model/MultiAccount.php†L56-L104】
- **Migration considerations:** Rename to `login_ips`, convert `ip` to unsigned big integer or string (IPv6 support), and enforce uniqueness per user/IP via compound index. Replace manual pruning with scheduled job using Laravel tasks.

## transfer_gold_log
- **Schema highlights:** Records gold transfers between users, including sender, recipient, VARCHAR amount, and timestamp.【F:main_script/include/schema/T4.4.sql†L145-L156】
- **Usage:** `Model\TransferGoldModel` writes to the log for every transfer and exposes pagination endpoints for admin review.【F:main_script/include/Model/TransferGoldModel.php†L34-L68】
- **Migration considerations:** Cast `amount` to integer, add `created_at`, and define foreign keys to `users`. We should surface a reason/status column so suspicious transfers can be flagged during moderation.

## banHistory (target: `ban_history`)
- **Schema highlights:** Stores historical bans with `uid`, reason, start time, and optional end time; indexed on `uid` for user drill-down.【F:main_script/include/schema/T4.4.sql†L1025-L1038】
- **Usage:** Multiple controllers append to this table when issuing bans and load history for admin/player views.【F:main_script/include/admin/include/Controllers/BannedListCtrl.php†L24-L71】【F:main_script/include/Controller/NachrichtenCtrl.php†L241-L334】
- **Migration considerations:** Rename to `ban_history` to follow snake_case, convert timestamps to `banned_at`/`unbanned_at`, and add a foreign key to `users`. Consider storing acting admin id and ban type for richer analytics.

## banQueue
- **Schema highlights:** Pending bans queue with `uid`, reason, start and end times, using camel-case name today.【F:main_script/include/schema/T4.4.sql†L1039-L1051】
- **Usage:** Detection scripts populate the queue, automation tasks expire entries, and admin controllers manage listings and approvals.【F:main_script/include/detect.php†L19-L68】【F:main_script/include/Core/Automation.php†L279-L282】
- **Migration considerations:** Rename table to `ban_queue`, enforce `uid` uniqueness to avoid duplicates, and include status flags (`pending`, `executed`, `expired`). Replace manual deletes with soft deletes when historical auditing is required.

## multiaccount_log
- **Schema highlights:** Dense log of suspected multi-account interactions (trades, reinforcements, attacks) storing both player ids, interaction type, and timestamp with supporting indexes.【F:main_script/include/schema/T4.4.sql†L393-L408】
- **Usage:** Multi-account service tallies recent activity between two users and records new findings through `addMultiAccountLog`.【F:main_script/include/Model/MultiAccount.php†L97-L111】
- **Migration considerations:** Promote timestamps, add enumerated type for interaction category, and introduce composite unique key to prevent duplicate entries within evaluation windows.

## multiaccount_users
- **Schema highlights:** Holds serialized multi-account suspicion batches with priority weighting and TTL-driven cleanup; unique index on `uid` ensures single pending record per user.【F:main_script/include/schema/T4.4.sql†L1738-L1750】
- **Usage:** Multi-account cron job seeds this table for moderators, while various controllers clear entries when bans are issued or lifted.【F:main_script/include/Model/MultiAccount.php†L7-L55】【F:main_script/include/admin/include/Controllers/MultiAccountCtrl.php†L65-L71】
- **Migration considerations:** Normalize the `data` payload into a related table or JSON column, expose `processed_at` timestamps, and align cleanup with Laravel's scheduler instead of inline deletes.

---

### Cross-cutting Migration Themes
- **Naming normalization:** Tables like `banHistory`/`banQueue` need snake_case renames and consistent foreign key naming in Laravel migrations.
- **Timestamp modernization:** Replace raw integers with timezone-aware `timestamp` columns and standardize on `created_at`/`updated_at` plus domain-specific markers (`banned_at`, `reviewed_at`).
- **Relationship enforcement:** Add foreign keys to `users` (offender, admin), `villages`, or other domain entities so Eloquent relations can reflect moderation workflows.
- **Index strategy:** Preserve existing indexes (e.g., `log_ip.uid`, `multiaccount_log.uid`) while adding compound indexes to support frequent queries highlighted above.
