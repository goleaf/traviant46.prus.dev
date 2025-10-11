# Game Mechanics Model Inventory

## 3. Combat Launch Payloads

### 3.2 Game Mechanics Models - Attack (from a2b table)
The legacy Travian rally point uses the `a2b` table as a transient store for attack payloads while a player walks through the
three-step send troops wizard. Each row caches the target tile and troop composition before the movement is converted into an
active march.

**Schema recap**

| Column | Purpose |
| --- | --- |
| `timestamp`, `timestamp_checksum` | Deduplication keys that bind a browser submission to its cached payload. |
| `to_kid` | Target tile/village identifier that will receive the attack. |
| `u1`–`u11` | Unit counts (race-specific infantry/cavalry plus the optional hero flag). |
| `attack_type` | Mission selector (reinforce, normal attack, raid, spy). |
| `redeployHero` | Boolean indicating whether the hero should change its home village after the attack. |

The table stores one row per queued send action with auto-incrementing `id` for lookup when the player returns to the
confirmation step.【F:main_script/include/schema/T4.4.sql†L1-L23】

**Lifecycle in the rally point controller**

1. **Creation.** After the player enters coordinates and unit counts, `sendTroops::prepare()` calculates validation results,
   generates the timestamp pair, and persists the request via `addA2b()` so it can render the confirmation dialog.【F:main_script/include/Controller/RallyPoint/sendTroops.php†L336-L420】【F:main_script/include/Controller/RallyPoint/sendTroops.php†L1043-L1069】
2. **Reload/Resume.** When the confirmation form posts back (e.g., due to validation errors or the player returning from the
   preview step), `getA2b()` fetches the cached row and rehydrates the UI with the stored unit payload, target metadata, and
   attack type, ensuring the wizard stays idempotent.【F:main_script/include/Controller/RallyPoint/sendTroops.php†L325-L370】
3. **Confirmation.** On the "send" action, the controller deletes the cached record via `removeA2b()` before opening a
   transaction that debits troops, calculates movement speed, and inserts the actual `movement` row. This prevents duplicate
   launches if the player resubmits the form or refreshes mid-send.【F:main_script/include/Controller/RallyPoint/sendTroops.php†L382-L399】【F:main_script/include/Controller/RallyPoint/sendTroops.php†L692-L999】
4. **Cleanup.** Hourly automation trims any stale `a2b` entries that were never confirmed so that abandoned payloads do not
   accumulate indefinitely.【F:main_script/include/Core/Automation.php†L399-L410】

**Domain rules captured in `prepare()`**

- Validates that the active village has not changed during the wizard and reloads coordinate/target metadata (including oasis
  ownership and alliance relationships) from the cached row before proceeding.【F:main_script/include/Controller/RallyPoint/sendTroops.php†L341-L399】
- Recomputes allowable attack types, hero redeployment options, and arrival estimates using the stored payload, making the
  confirmation screen authoritative even if the player edits query parameters client-side.【F:main_script/include/Controller/RallyPoint/sendTroops.php†L356-L580】

**Modernisation considerations**

- Replace the monolithic `u1`–`u11` columns with a child table or JSON payload keyed by unit identifier so race-specific troop
  types remain explicit when ported to Eloquent models.
- Promote `attack_type` and `redeployHero` to typed enums/booleans and capture the origin village ID to make queued attacks
  self-contained (currently the origin is inferred from session state during confirmation).【F:main_script/include/Controller/RallyPoint/sendTroops.php†L336-L420】【F:main_script/include/Controller/RallyPoint/sendTroops.php†L709-L999】
- Record timestamps (`created_at`, `expires_at`) rather than relying on cron-driven deletion so future Laravel jobs can expire
  cached payloads predictably.【F:main_script/include/Core/Automation.php†L399-L410】
