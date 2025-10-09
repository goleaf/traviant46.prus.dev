# Normalizing Generic Column Names

## Problem Summary
Legacy Travian schema tables such as `fdata` and `units` rely on numbered columns (`f1`-`f40`, `u1`-`u10`) to capture building slot types and stationed troop counts. The paired `ft` columns store type metadata, while `u11`/`u99` overload special cases. This design obscures semantics, complicates queries, and makes extending the data model error-prone.

- `fdata` stores 40 field slots using `f1`…`f40` and separate type columns `f1t`…`f40t` plus wonder metadata (`embassy`, `heroMansion`, `wwname`).【F:main_script/include/schema/T4.4.sql†L562-L654】
- `units` tracks village troop inventories via `u1`…`u10`, `u11`, and `u99`, keyed by village id (`kid`) and tribe (`race`).【F:main_script/include/schema/T4.4.sql†L1392-L1409】

## Normalization Objectives
1. **Human-readable semantics** – Replace positional columns with rows keyed by slot/unit identifiers so that each record describes a single building instance or troop count.
2. **Extensibility** – Allow new building types or unit variants without schema changes by referencing lookup tables (`building_types`, `unit_types`).
3. **Relational integrity** – Enforce foreign keys between villages, building slots, and unit inventories; remove duplicated tribe columns once type metadata resides in lookup tables.
4. **Auditability** – Introduce timestamps (`created_at`, `updated_at`) and optional `deleted_at` to capture lifecycle events for construction and troop movements.

## Target Table Design
### Buildings
- **`village_buildings`**: `id`, `village_id`, `slot_number`, `building_type_id`, `level`, `is_under_construction`, timestamps.
- **`building_slots`** (optional dictionary): map `slot_number` to allowed building categories (resource field vs. village center) for validation rules.
- **`building_types`**: canonical list of building definitions (legacy `fXt` values) with attributes used by gameplay logic.
- **`world_wonder_states`**: isolate wonder-specific columns (`lastWWUpgrade`, `wwname`, `embassy`, `heroMansion`) with foreign keys to the owning village.

### Units
- **`village_units`**: `id`, `village_id`, `unit_type_id`, `quantity`, optional `status` enum (home, reinforcements, prisoners).
- **`unit_types`**: dictionary for each troop variant keyed by tribe and legacy slot (`u1`…`u10`) with combat stats and training costs.
- **`unit_reserves` / `unit_movements`**: child tables for garrisons, outgoing waves, or trapped troops replacing `enforcement`, `trapped`, and similar wide schemas.

## Migration Strategy
1. **Lookup bootstrap**: populate `building_types` and `unit_types` by translating legacy enumerations (`fXt`, `uN`) into descriptive identifiers.
2. **Slot migration**: iterate villages, explode `fdata` rows into `village_buildings`, deriving slot numbers from column suffixes and mapping `fNt` to `building_type_id`.
3. **Unit migration**: transform `units` rows into `village_units` grouped by `unit_type_id`, merging `u11`/`u99` into explicit type rows (e.g., `hero`, `settlers`).
4. **Foreign key enforcement**: add constraints linking `village_buildings.village_id` → `villages.id` and `village_units.unit_type_id` → `unit_types.id`.
5. **Legacy deprecation**: once application logic reads the normalized tables, drop obsolete columns/tables or retain read-only views for historical exports.

## Implementation Notes
- Use Laravel migrations to create the normalized structures with cascading deletes aligning to village lifecycle rules.
- Write dedicated ETL commands (Artisan console) to backfill data, ensuring batch processing to avoid long transactions.
- Update domain services to rely on repository/ORM abstractions, preventing direct reliance on positional column names during the transition.
- Maintain mapping constants within code to bridge legacy enumerations until the new lookup tables are authoritative.
