# Communication Components

This document summarizes the responsibilities and data flows covered by the legacy message and report controllers. It should serve as a reference when creating Livewire components under `App\\Livewire`.

## Messages

### `App\\Livewire\\Messages\\Inbox`
- Loads the player's inbox with pagination and optional recursive loading across villages (`o` query flag).【F:_travian/main_script_dev/include/Controller/NachrichtenCtrl.php†L259-L337】
- Guards bulk actions (mark read/unread, archive, delete) behind sitter permissions and the Gold Club entitlement.【F:_travian/main_script_dev/include/Controller/NachrichtenCtrl.php†L263-L295】【F:_travian/main_script_dev/include/Controller/NachrichtenCtrl.php†L284-L289】
- Renders sender metadata, including alliance ambassadors, support, and multihunter indicators, while formatting timestamps through `TimezoneHelper::autoDateString`.【F:_travian/main_script_dev/include/Controller/NachrichtenCtrl.php†L303-L325】
- Provides pagination controls via `PageNavigator` and surfaces empty states when no messages are present.【F:_travian/main_script_dev/include/Controller/NachrichtenCtrl.php†L327-L338】

### `App\\Livewire\\Messages\\Compose`
- Validates message submissions for length, spam heuristics, sitter permissions, and alliance/admin broadcast scenarios before sending.【F:_travian/main_script_dev/include/Controller/NachrichtenCtrl.php†L341-L453】
- Surfaces contextual error messages for long subjects, insufficient population, spam throttling, or excessive recipients.【F:_travian/main_script_dev/include/Controller/NachrichtenCtrl.php†L455-L471】
- Manages the in-game address book, including friend invitations, acceptance, and status indicators for recent activity.【F:_travian/main_script_dev/include/Controller/NachrichtenCtrl.php†L472-L589】
- Prefills reply metadata by quoting the previous message and adjusting the subject line when replying to existing messages.【F:_travian/main_script_dev/include/Controller/NachrichtenCtrl.php†L500-L536】

## Reports

### `App\\Livewire\\Reports\\ReportList`
- Initializes report filters per category, honoring stored player preferences and the Gold Club recursive view option.【F:_travian/main_script_dev/include/Controller/BerichteCtrl.php†L1232-L1360】
- Fetches paginated report data, optionally constrained by loss percentage, and builds table rows through `reportOverviewLayout`.【F:_travian/main_script_dev/include/Controller/BerichteCtrl.php†L1263-L1299】【F:_travian/main_script_dev/include/Controller/BerichteCtrl.php†L1391-L1400】
- Provides navigation metadata (page number, recursive flag, filter state) required for rebuilding the list on interaction.【F:_travian/main_script_dev/include/Controller/BerichteCtrl.php†L1258-L1297】

### `App\\Livewire\\Reports\\BattleReport`
- Determines available actions (forward, archive, repeat attack, add to farm list) based on ownership, report type, and premium features.【F:_travian/main_script_dev/include/Controller/BerichteCtrl.php†L200-L262】
- Builds context-specific payloads for diverse report types such as caged animals, new villages, trade deliveries, reinforcements, and adventures.【F:_travian/main_script_dev/include/Controller/BerichteCtrl.php†L270-L360】
- Integrates helper services like `NoticeHelper`, `FarmListModel`, and `Formulas::kid2xy` to enrich the rendered report view.【F:_travian/main_script_dev/include/Controller/BerichteCtrl.php†L221-L236】【F:_travian/main_script_dev/include/Controller/BerichteCtrl.php†L262-L320】

> **Implementation note:** When porting these flows to Livewire, ensure that authorization checks (sitter permissions, Gold Club, Plus features) and anti-spam safeguards remain enforced server-side to match current gameplay rules.
