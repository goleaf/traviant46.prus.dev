# Movement & Combat Table Migration Notes

The Travian T4.6 schema spreads combat state across multiple tables. Migrating these rows
without downtime requires understanding how each table contributes to an attack's lifecycle.
The following notes document the critical fields, relationships, and validation steps for the
high-risk movement/combat tables mentioned in the migration plan.

## a2b — Pending Attack Payloads
- Stores the initial payload that a player submits when launching an attack, before it is
  converted into an active movement. Each row captures the destination `to_kid`, unit counts,
  attack type, and whether the hero redeploys with the wave.【F:main_script/include/schema/T4.4.sql†L1-L23】
- The `timestamp` and `timestamp_checksum` pair is used to deduplicate submissions and prevent
  tampering. When replaying or auditing attack launches, recalculate the checksum after import.
- Migration strategy:
  - Drain the command queue or pause the game before copying rows to ensure the application
    does not reprocess partially migrated submissions.
  - Reconcile against the `movement` table immediately after cutover: every `a2b` row should
    have either spawned a matching `movement` entry or have been safely discarded.

## movement — Active Troop Movements
- Represents every in-flight troop action (attacks, reinforcements, raids, hero returns, etc.)
  with origin/destination `kid` pairs, unit payload, targeting options (`ctar1`, `ctar2`,
  `spyType`), and mission fields such as `mode` and `attack_type` that drive combat
  resolution.【F:main_script/include/schema/T4.4.sql†L1072-L1109】
- Migration strategy:
  - Export this table inside a transaction with `units`, `enforcement`, and `trapped` so troop
    counts remain consistent.
  - Record the maximum `end_time` migrated and block new actions until all movements with an
    `end_time` ≤ that value have been processed in the destination environment.
  - The Laravel job `App\Jobs\MovementResolverJob` now emits
    `App\Domain\Game\Troop\Events\TroopsArrived` and
    `App\Domain\Game\Combat\Events\CombatResolved` after processing so the rally point UI
    synchronises instantly—ensure queues remain enabled for broadcasting post-migration.

## enforcement — Stationed Reinforcements
- Holds friendly troops stationed in a foreign village. Rows reference both origin (`kid`) and
  destination (`to_kid`) villages along with the owning `uid` and troop counts.【F:main_script/include/schema/T4.4.sql†L500-L524】
- Migration strategy:
  - Validate that every reinforcement row aligns with a garrison record in the origin
    village's `units` entry to prevent troop duplication.
  - Cross-check the `to_kid` against `movement` rows in flight to avoid double-counting
    reinforcements that are still travelling.

## trapped — Captured Troops
- Tracks troops trapped in gaul villages, including the capturing village (`to_kid`) and the
  owner (`kid`) for release or execution events.【F:main_script/include/schema/T4.4.sql†L1368-L1390】
- Migration strategy:
  - Ensure that trapped troop counts subtract from the owning village's `units` home totals.
  - After import, schedule a verification pass that simulates trap releases to confirm the
    destination code correctly frees or returns units.

## units — Home Garrison Totals
- Maintains the stationed troop totals for each village (`kid`) split by race and unit type,
  including the hero column (`u11`) and celebration slot (`u99`).【F:main_script/include/schema/T4.4.sql†L1393-L1409】
- Migration strategy:
  - Copy in sync with `movement`, `enforcement`, and `trapped` to preserve conservation of
    troops. Any discrepancy between the sum of home units + reinforcements + movements should
    raise an alert before go-live.
  - Recalculate resource upkeep after import if the target system stores upkeep in derived
    caches.

## send — Resource Shipments
- Contains ongoing resource transfers between villages, recording the payload per resource
  type, travel mode, and arrival time.【F:main_script/include/schema/T4.4.sql†L1291-L1309】
- Migration strategy:
  - Freeze marketplace actions before export so merchants launched mid-migration are not lost.
  - After import, validate that the `end_time` aligns with merchant travel speeds in the new
    world configuration (especially if speed multipliers changed).

## Post-Migration Integrity Checklist
1. Choose a cutoff time, pause world actions, and export `a2b`, `movement`, `enforcement`,
   `trapped`, `units`, and `send` in the same transaction.
2. After import, run a reconciliation script that:
   - Confirms every `a2b` entry either has a corresponding `movement` row or is queued for
     deletion.
   - Verifies conservation of unit counts across `units`, `movement`, `enforcement`, and
     `trapped` for each village and player.
   - Checks that every `send` row has a valid source/destination village and that merchant
     counts match available slots.
3. Only reopen the world when reconciliation passes and the maximum migrated `end_time` has
   elapsed or been re-queued inside the destination environment.
