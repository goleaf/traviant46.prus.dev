# Farmlist & Raidlist Migration Notes

## Scope
This note covers the trio of legacy tables that power Travian's farm-list automation:

- `farmlist` — the player's collection of raid targets for a specific village.【F:main_script/include/schema/T4.4.sql†L548-L561】
- `raidlist` — individual slots inside a farm list, defining the destination village, cached distance, and troop payload.【F:main_script/include/schema/T4.4.sql†L1255-L1276】
- `farmlist_last_reports` — per-target pointers to the latest combat report displayed in the UI.【F:main_script/include/schema/T4.4.sql†L1829-L1839】

Together they orchestrate manual and automated raids, interact with troop counts (`units`), combat movements (`movement`), reports (`ndata`), and several guard rails such as farm list throttling and vacation protection.

## Legacy responsibilities

| Table | Responsibility |
| --- | --- |
| `farmlist` | Stores the owning player, the launching village, the list name, and automation timers (`auto`, `lastRaid`, `randSec`).【F:main_script/include/schema/T4.4.sql†L548-L556】|
| `raidlist` | Contains one row per target slot with the destination `kid`, cached travel `distance`, and ten troop columns (`u1`–`u10`) that mirror unit types 1–10 for the owner's race.【F:main_script/include/schema/T4.4.sql†L1258-L1274】|
| `farmlist_last_reports` | Links a player and target village to the `ndata` report id shown in the farm list detail view, allowing “new report” badges without re-querying the full reports table.【F:main_script/include/schema/T4.4.sql†L1829-L1839】|

## Application interaction patterns

* **CRUD + slot management:** `FarmListModel` wraps all list operations: creating lists, checking slot uniqueness, inserting slots, and deleting them while cascading slot removal.【F:main_script_dev/include/Model/FarmListModel.php†L79-L143】【F:main_script_dev/include/Model/FarmListModel.php†L248-L305】  Slot payloads are materialised directly into the ten unit columns and reused when launching raids.【F:main_script_dev/include/Model/FarmListModel.php†L121-L131】
* **Automation scheduling:** The model's `processAutoRaid()` fetches every list flagged `auto=1` whose `(lastRaid + randSec)` window has elapsed, updates the timer, and triggers `autoRaidFarmList()` to launch raids while charging silver.【F:main_script_dev/include/Model/FarmListModel.php†L378-L399】  `Automation::autoFarmlist()` runs this routine inside the cron-style worker loop.【F:main_script_dev/include/Core/Automation.php†L65-L69】
* **Raid dispatch:** `autoRaidFarmList()` iterates each slot, checks protection/vacation rules, verifies available units, deducts troops, and enqueues a `movement` row for each successful dispatch.【F:main_script_dev/include/Model/FarmListModel.php†L403-L490】  The method also logs before/after troop snapshots for auditing.【F:main_script_dev/include/Model/FarmListModel.php†L424-L488】
* **UI integrations:** `getLastReport()` joins into `farmlist_last_reports` to fetch the latest report metadata for a slot, while `getLastTargets()` surfaces recent raid targets for quick-add dialogs.【F:main_script_dev/include/Model/FarmListModel.php†L218-L355】  Controllers throttle editing and sending actions through `FarmlistTracker`, which enforces captcha-backed rate limits via cached counters.【F:main_script_dev/include/Core/FarmlistTracker.php†L51-L96】

## Migration and redesign considerations

1. **Preserve referential integrity:** Neither `farmlist` nor `raidlist` currently enforce foreign keys. Introduce `foreignId` columns (`owner_id`, `village_id`, `farm_list_id`, `target_village_id`) referencing redesigned `users` and `villages` tables, and add cascading deletes so list cleanup happens automatically when a village changes ownership.
2. **Normalise troop payloads:** The `u1`–`u10` columns create wide tables and complicate race-agnostic logic. In Laravel, model slots as a JSON column (`troop_payload`) or a child table (`farm_list_slot_units`) with `unit_type`/`amount` pairs. This simplifies validation and makes unit additions (e.g., special units) schema-neutral.
3. **Timekeeping upgrades:** Replace the integer timer columns (`lastRaid`, `randSec`) with explicit timestamps (`last_queued_at`, `cooldown_seconds`) stored as `timestamp`/`unsignedInteger`. This clarifies scheduling logic and plays nicely with Carbon when porting `processAutoRaid()` to a queued job.
4. **Job-driven automation:** Instead of polling in a cron script, push auto-raid scheduling into Laravel queues. A `DispatchFarmList` job can evaluate a single list, pull slot definitions, deduct troops, and emit `Movement` models. Leverage database transactions to ensure that troop deductions and movement creation stay atomic.
5. **Report pointers:** When redesigning `farmlist_last_reports`, use foreign keys to the new `reports` table and add timestamps (`updated_at`) so you can prune stale pointers. Consider deriving the “latest report” view with an indexed query instead of a manual pointer if the reports table gains proper composite keys.
6. **Security & rate limits:** Port `FarmlistTracker` into a dedicated throttling middleware backed by Redis. Enforce captcha or per-minute send limits declaratively (e.g., Laravel's `ThrottleRequests`) and link them to configurable settings so live ops can tune farm-list spam protection without code edits.
7. **Data migration checklist:**
   - Export `farmlist`, `raidlist`, and `farmlist_last_reports` together with `units` and in-flight `movement` rows to keep troop availability consistent at the cutover point.
   - During import, remap legacy `kid` identifiers to the new village primary keys before reconstructing slot references.
   - Recalculate cached `distance` columns if the world map size or coordinate system changes; otherwise carry them over to avoid first-use recalculations.
   - After migration, run a reconciliation script that confirms every automated raid deducted the right troop counts and that UI report pointers still resolve to valid combat reports.

## Recommended Laravel modelling

* `FarmList` Eloquent model with relationships: `belongsTo(User::class, 'owner_id')`, `belongsTo(Village::class, 'village_id')`, `hasMany(FarmListSlot::class)`.
* `FarmListSlot` model holding `target_village_id`, `distance`, `troop_payload` (JSON) plus audit timestamps, with eager-loaded `targetVillage` relations for UI rendering.
* `FarmListReportPointer` model referencing both `FarmListSlot` and `Report` to track the latest outcome, enabling `hasOne` relationships for quick access.
* Queueable `DispatchFarmList` and `DispatchFarmListSlot` jobs to replace synchronous loops, unlocking horizontal scaling and clearer retry semantics for failed raid dispatches.

These changes modernise the farm-list subsystem, reduce schema brittleness, and make automated raids observable and resilient during the Laravel migration.
