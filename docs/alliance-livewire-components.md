# Alliance Livewire Components

This document summarizes the responsibilities that need to be covered by the planned Livewire components that will replace the current imperative controllers inside `_travian/main_script_dev`. Each section maps existing behaviour to a prospective Livewire class so the migration can focus on parity before introducing new UX improvements.

## `App\Livewire\Alliance\AllianceProfile`
* Chooses between the description and member list views based on the current request or the player's saved favourite tab, and lets authorised players update both alliance descriptions. 【F:_travian/main_script_dev/include/Controller/AllianceCtrl.php†L104-L121】
* Enriches the profile view with alliance metadata such as rank, tag, name, and whether an external forum is linked. 【F:_travian/main_script_dev/include/Controller/AllianceCtrl.php†L122-L133】
* Builds the member table, including totals, role badges, vacation flags, sitter tools, and in-alliance online status indicators powered by `AutomationModel`. 【F:_travian/main_script_dev/include/Controller/AllianceCtrl.php†L134-L170】

## `App\Livewire\Alliance\AllianceForum`
* Redirects to a custom URL when one is configured, otherwise renders the alliance forum toolchain. 【F:_travian/main_script_dev/include/Controller/AllianceCtrl.php†L173-L181】
* Wraps the legacy `Controller\AllianceForum` dispatcher, which toggles admin mode, manages forums/topics/posts, handles polls, and enforces permission checks for each action. 【F:_travian/main_script_dev/include/Controller/AllianceForum.php†L22-L200】

## `App\Livewire\Alliance\AllianceMembers`
* Centralises the logic that currently lives inside `showAllianceProfile()` to compute aggregate population, format the roster table, and expose rank, village counts, and communication shortcuts for every member. 【F:_travian/main_script_dev/include/Controller/AllianceCtrl.php†L134-L170】
* Should expose hooks for sitter awareness and role presentation so that Livewire views can reuse existing HTML templates (e.g., `alliance/Profile`) without duplicating PHP string assembly.

## `App\Livewire\Alliance\AllianceDiplomacy`
* Reproduces `processAllianceDiplomacy()` by validating outgoing offers, persisting them, logging alliance events, and updating cached diplomacy data. 【F:_travian/main_script_dev/include/Controller/AllianceCtrl.php†L582-L635】
* Provides actions to accept, refuse, or cancel offers and keeps the offer tables (`ownOffers`, `foreign`, `exiting`) in sync with the database. 【F:_travian/main_script_dev/include/Controller/AllianceCtrl.php†L636-L725】

## `App\Livewire\Alliance\ForumTopic`
* Fetches forum metadata, enforces area-specific access rules, and renders the topic table (including counts and last post details) for the currently selected alliance forum. 【F:_travian/main_script_dev/include/Controller/AllianceForum.php†L808-L848】【F:_travian/main_script_dev/include/Controller/AllianceForum.php†L850-L876】
* Surfaces moderator controls for moving, locking, pinning, and deleting topics, while showing folder-state icons for regular members based on unread status. 【F:_travian/main_script_dev/include/Controller/AllianceForum.php†L40-L104】【F:_travian/main_script_dev/include/Controller/AllianceForum.php†L878-L920】
* Handles the new-topic workflow, including poll creation, survey scheduling, and redirecting to the freshly created thread once validation succeeds. 【F:_travian/main_script_dev/include/Controller/AllianceForum.php†L106-L188】【F:_travian/main_script_dev/include/Controller/AllianceForum.php†L328-L443】

## `App\Livewire\Alliance\ForumPost`
* Governs post creation inside a topic by honouring sitter restrictions, checksum validation, and topic lock state before writing replies. 【F:_travian/main_script_dev/include/Controller/AllianceForum.php†L139-L188】【F:_travian/main_script_dev/include/Controller/AllianceForum.php†L328-L374】
* Provides edit and delete flows that re-check forum ownership, author permissions, and CSRF tokens before updating or removing stored messages. 【F:_travian/main_script_dev/include/Controller/AllianceForum.php†L40-L80】【F:_travian/main_script_dev/include/Controller/AllianceForum.php†L230-L285】
* Renders the paginated post list with player metadata, BBCode formatting, and edit history annotations for the Livewire view to consume. 【F:_travian/main_script_dev/include/Controller/AllianceForum.php†L499-L566】

By isolating the above behaviours into Livewire components, we can replace the procedural controller with reactive views while maintaining feature completeness for description editing, roster management, forum discussions, and diplomacy workflows.
