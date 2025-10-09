# Alliance Livewire Components

This document summarizes the responsibilities that need to be covered by the planned Livewire components that will replace the current imperative controllers inside `main_script_dev`. Each section maps existing behaviour to a prospective Livewire class so the migration can focus on parity before introducing new UX improvements.

## `App\Livewire\Alliance\AllianceProfile`
* Chooses between the description and member list views based on the current request or the player's saved favourite tab, and lets authorised players update both alliance descriptions. 【F:main_script_dev/include/Controller/AllianceCtrl.php†L104-L121】
* Enriches the profile view with alliance metadata such as rank, tag, name, and whether an external forum is linked. 【F:main_script_dev/include/Controller/AllianceCtrl.php†L122-L133】
* Builds the member table, including totals, role badges, vacation flags, sitter tools, and in-alliance online status indicators powered by `AutomationModel`. 【F:main_script_dev/include/Controller/AllianceCtrl.php†L134-L170】

## `App\Livewire\Alliance\AllianceForum`
* Redirects to a custom URL when one is configured, otherwise renders the alliance forum toolchain. 【F:main_script_dev/include/Controller/AllianceCtrl.php†L173-L181】
* Wraps the legacy `Controller\AllianceForum` dispatcher, which toggles admin mode, manages forums/topics/posts, handles polls, and enforces permission checks for each action. 【F:main_script_dev/include/Controller/AllianceForum.php†L22-L200】

## `App\Livewire\Alliance\AllianceMembers`
* Centralises the logic that currently lives inside `showAllianceProfile()` to compute aggregate population, format the roster table, and expose rank, village counts, and communication shortcuts for every member. 【F:main_script_dev/include/Controller/AllianceCtrl.php†L134-L170】
* Should expose hooks for sitter awareness and role presentation so that Livewire views can reuse existing HTML templates (e.g., `alliance/Profile`) without duplicating PHP string assembly.

## `App\Livewire\Alliance\AllianceDiplomacy`
* Reproduces `processAllianceDiplomacy()` by validating outgoing offers, persisting them, logging alliance events, and updating cached diplomacy data. 【F:main_script_dev/include/Controller/AllianceCtrl.php†L582-L635】
* Provides actions to accept, refuse, or cancel offers and keeps the offer tables (`ownOffers`, `foreign`, `exiting`) in sync with the database. 【F:main_script_dev/include/Controller/AllianceCtrl.php†L636-L725】

By isolating the above behaviours into Livewire components, we can replace the procedural controller with reactive views while maintaining feature completeness for description editing, roster management, forum discussions, and diplomacy workflows.
