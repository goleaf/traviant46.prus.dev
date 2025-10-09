# Miscellaneous Support Tables Migration Notes

## Scope
This note documents the eleven legacy tables grouped as "Misc Tables" in the migration plan: `links`, `infobox`, `infobox_read`, `infobox_delete`, `ignoreList`, `friendlist`, `changeEmail`, `notificationQueue`, `voting_reward_queue`, `buyGoldMessages`, and `player_references`. These tables power personalised UI widgets, player relationship management, queued notifications, monetisation workflows, and referral rewards in the Travian 4.6 codebase.

## Legacy responsibilities overview

| Table | Purpose summary |
| --- | --- |
| `links` | Stores per-player quick links that surface in the sidebar UI. |
| `infobox`, `infobox_read`, `infobox_delete` | Manage time-bound announcements with per-player read/delete tracking. |
| `ignoreList` | List of players blocked from sending messages. |
| `friendlist` | Handles friend invitations and acceptance state. |
| `changeEmail` | Temporary store for pending email changes and confirmation codes. |
| `notificationQueue` | Buffer for out-of-game notifications waiting to be pushed to the global notification service. |
| `voting_reward_queue` | Queue of pending external voting rewards awaiting gold payout. |
| `buyGoldMessages` | Queue of premium purchase/voucher confirmation messages. |
| `player_references` | Referral programme tracking and payout status. |

## Table-by-table analysis

### `links`
* Schema: records `uid`, display name, target URL, and an ordering column for each user-defined shortcut.【F:main_script/include/schema/T4.4.sql†L839-L851】
* Access patterns: the `LinksModel` fetches, inserts, updates, and deletes entries per player, sanitising names/URLs and maintaining ordering.【F:main_script/include/Model/LinksModel.php†L7-L60】 The sidebar view renders cached link lists with optional "open in new tab" styling.【F:main_script/include/resources/View/GameView.php†L601-L646】
* Migration recommendations:
  * Promote to a Laravel model (`UserLink`) with a foreign key to `users` and soft deletes so players can restore default links.
  * Replace the free-text URL sanitisation with Laravel validation rules to prevent malformed URLs.
  * Expose CRUD endpoints via Livewire/Blade components; cache the rendered list using Laravel cache tags instead of manual Memcache keys.

### `infobox`, `infobox_read`, `infobox_delete`
* Schema: `infobox` stores announcement metadata (audience scope, type, params payload, read/delete flags, and display window). `infobox_read` and `infobox_delete` track which public notices a player has read or hidden.【F:main_script/include/schema/T4.4.sql†L1641-L1685】
* Access patterns: `InfoBoxModel` inserts notices, enforces per-type uniqueness, fetches current messages with caching, and records read/delete actions across the helper tables.【F:main_script/include/Model/InfoBoxModel.php†L33-L220】 Admin controllers seed global entries and purge expired rows. Automation also prunes expired public notices.【F:main_script/include/Core/Automation.php†L884-L885】
* Migration recommendations:
  * Collapse the trio into Laravel models with explicit foreign keys: `infoboxes`, `infobox_reads`, and `infobox_hides` (or soft deletes on a pivot).
  * Represent `params` as JSON columns to avoid manual escaping and allow typed casting in Eloquent.
  * Replace manual caching (`InfoBox:Private:$uid`) with cache tags or database-backed `read_at` timestamps; leverage Laravel's notification broadcasting for global messages.
  * Model `type` as an enum (backed by a lookup table) so business rules stay discoverable.

### `ignoreList`
* Schema: simple mapping of player → ignored player with a composite index for lookups.【F:main_script/include/schema/T4.4.sql†L1414-L1425】
* Access patterns: messaging logic inserts rows when a player chooses to ignore another user, enforces a maximum of 20 entries, and reuses the table for filtering friend/village searches.【F:main_script/include/Model/MessageModel.php†L52-L105】【F:main_script/include/resources/View/GameView.php†L71-L79】
* Migration recommendations:
  * Convert to a dedicated pivot (`ignored_users`) with foreign keys (`user_id`, `ignored_user_id`) and unique constraints to prevent duplicates.
  * Enforce the size cap at the application layer (validation rule) and surface friendly error messages when the limit is reached.
  * Use cascading deletes so that removing a user automatically clears the related ignore rows.

### `friendlist`
* Schema: records inviter, invitee, and whether the friendship request was accepted.【F:main_script/include/schema/T4.4.sql†L1427-L1438】
* Access patterns: `MessageModel` manages invitations, acceptance, counting, and removal; the UI fetches both pending and approved entries for messaging conveniences.【F:main_script/include/Model/MessageModel.php†L13-L188】【F:main_script/include/Controller/NachrichtenCtrl.php†L548-L550】
* Migration recommendations:
  * Represent as a mutual relationship pivot (`friendships`) with status enum (`pending`, `accepted`, `blocked`) and timestamps for auditing.
  * Add unique constraints to avoid duplicate rows per user pair and support symmetrical lookups via Eloquent relationships.
  * Introduce policies to guard acceptance/deletion actions through Laravel authorisation instead of manual ID checks.

### `changeEmail`
* Schema: keyed by `uid`, storing pending email, and two short confirmation codes used in the dual-email verification process.【F:main_script/include/schema/T4.4.sql†L1440-L1451】
* Access patterns: `OptionModel` checks for pending requests, inserts new codes, and deletes completed entries; email uniqueness checks query `changeEmail` alongside `users` and `activation`.【F:main_script/include/Model/OptionModel.php†L200-L308】
* Migration recommendations:
  * Replace with Laravel's built-in email verification tokens or a dedicated `email_change_requests` model that tracks requester IP, expiry, and status.
  * Store hashed verification tokens instead of short plaintext codes, and add timestamps to enforce expiration.
  * Create queued notification jobs to send the old/new confirmation emails using Laravel notifications.

### `notificationQueue`
* Schema: stores pending notification payloads and enqueue timestamps.【F:main_script/include/schema/T4.4.sql†L1751-L1760】
* Access patterns: helper `Notification::notify` pushes formatted HTML/text into the queue, and the automation cron processes batches, forwarding them to the global `notifications` table before deleting the rows.【F:main_script/include/Core/Helper/Notification.php†L12-L24】【F:main_script/include/Core/Automation.php†L893-L897】
* Migration recommendations:
  * Replace the bespoke table with Laravel's queue system (Redis or database driver) and dispatch notification jobs directly.
  * Model the notification payload as structured data (JSON) with recipient metadata instead of pre-rendered HTML strings.
  * Integrate with Laravel's `Notification` facade so email/in-app delivery channels share code paths.

### `voting_reward_queue`
* Schema: enqueues user IDs and the external voting site identifier awaiting reward processing.【F:main_script/include/schema/T4.4.sql†L1802-L1812】
* Access patterns: the automation loop processes up to 50 entries at a time, deletes them, credits `gift_gold` based on config, and sends an in-game message.【F:main_script/include/Core/Automation.php†L247-L259】
* Migration recommendations:
  * Model as a Laravel job or event (`GrantVotingReward`) with payload validation and idempotency keys to avoid duplicate payouts.
  * Persist audit logs of processed rewards (timestamp, awarded gold, source) for support visibility.
  * Add foreign keys to `users` and a status column (`pending`, `processed`, `skipped`) if the table remains relational during transition.

### `buyGoldMessages`
* Schema: queue for premium purchase confirmations with user ID, gold amount, type, and tracking code.【F:main_script/include/schema/T4.4.sql†L970-L981】
* Access patterns: voucher and payment flows insert rows; the automation job reads 50 at a time, deletes them, and sends templated messages to the user depending on `type`.【F:main_script/include/Core/Voucher.php†L90-L118】【F:main_script/include/Core/Automation.php†L261-L272】
* Migration recommendations:
  * Replace with Laravel events/notifications triggered directly when a purchase completes, eliminating the polling queue.
  * Store purchase receipts in a transactional table keyed to payment records with foreign keys and timestamps for reconciliation.
  * Use typed enums for message types (`direct_purchase`, `voucher`) rather than bare integers.

### `player_references`
* Schema: tracks referrer (`ref_uid`), referred player (`uid`), and reward status (`rewardGiven`).【F:main_script/include/schema/T4.4.sql†L1578-L1590】
* Access patterns: activation inserts new rows whenever a referral code is supplied; the automation `referenceCheck` job validates milestone completion, enforces per-referrer limits, and credits gold. The payment wizard AJAX endpoint lists pending referrals for the current player.【F:main_script/include/Controller/ActivateCtrl.php†L249-L255】【F:main_script/include/Core/Automation.php†L288-L310】【F:main_script/include/Controller/Ajax/paymentWizardAdvertisedPersons.php†L21-L50】
* Migration recommendations:
  * Promote to a first-class Eloquent model with timestamps (`referred_at`, `rewarded_at`) and explicit foreign keys to `users`.
  * Represent reward status as an enum and track milestone progress (e.g., `villages_count`) to avoid repeated queries.
  * Emit domain events when referrals complete so marketing/analytics systems can subscribe without scraping the database.

## Migration checklist highlights
* Create migrations to rename tables to snake_case Laravel conventions (`friendlist` → `friendships`, `ignoreList` → `ignored_users`, etc.) while adding foreign key constraints and timestamps.
* Replace manual polling queues (`notificationQueue`, `voting_reward_queue`, `buyGoldMessages`) with Laravel queue jobs so work distribution and retries become standardised.
* Centralise referral, voting, and premium purchase logic into service classes to de-duplicate the reward checks currently scattered across controllers, models, and cron jobs.
