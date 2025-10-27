# Legacy Controller Mapping

This inventory maps every actionable screen under `/_travian/main_script/include/Controller` to its Laravel Livewire or HTTP controller counterpart. Items flagged as **(planned)** represent components that will be created as their respective gameplay slices migrate into the modern stack.

## Primary world screens

| Legacy class | Purpose | Laravel target |
| --- | --- | --- |
| `ActivateCtrl` | Activation token landing page | `App\Http\Controllers\Auth\ActivationController` **(planned)** |
| `AllianceCtrl` | Alliance overview hub | `App\Livewire\Alliance\Overview` **(planned)** |
| `AllianceForum` | Alliance internal forum index | `App\Livewire\Alliance\ForumBoard` **(planned)** |
| `AnyCtrl` | Dynamic include wrapper for CMS pages | `App\Http\Controllers\Content\DynamicPageController` **(planned)** |
| `BannedCtrl` | Legacy ban landing (pre-login) | `App\Livewire\Account\BannedNotice` |
| `BerichteCtrl` | Reports inbox | `App\Livewire\Reports\Inbox` **(planned)** |
| `CreditsCtrl` | Travian credits explainer | `App\Livewire\Account\Credits` **(planned)** |
| `CropfinderCtrl` | Crop finder map | `App\Livewire\Map\CropFinder` **(planned)** |
| `Dorf1Ctrl` | Village resource overview | `App\Livewire\Village\Overview` |
| `Dorf2Ctrl` | Village building overview | `App\Livewire\Village\Infrastructure` **(planned)** |
| `Dorf3Ctrl` | Account village list | `App\Livewire\Village\Directory` **(planned)** |
| `EmailVerificationCtrl` | In-game verification prompt | `App\Livewire\Account\VerificationPrompt` |
| `EmailVerificationUrlCtrl` | Verification success redirect | `App\Http\Controllers\Auth\VerificationRedirectController` **(planned)** |
| `GameCtrl` | Base guard for in-game screens | Replaced by middleware stack (`EnsureGameIsAccessible`, `EnsureAccountNotBanned`, `EnsureAccountVerified`) |
| `HelpCtrl` | Game help index | `App\Livewire\Content\HelpIndex` **(planned)** |
| `HeroAdventureCtrl` | Hero adventure list | `App\Livewire\Hero\Adventures` **(planned)** |
| `HeroAuctionCtrl` | Hero auction house | `App\Livewire\Hero\Auctions` **(planned)** |
| `HeroBodyCtrl` | Hero customization | `App\Livewire\Hero\Customization` **(planned)** |
| `HeroFaceCtrl` | Hero face editor | `App\Livewire\Hero\Appearance` **(planned)** |
| `HeroInventoryCtrl` | Hero inventory | `App\Livewire\Hero\Inventory` **(planned)** |
| `HeroDivider` | Adventure reward splitter | `App\Livewire\Hero\Rewards` **(planned)** |
| `Hero_imageCtrl` | Hero portrait delivery | `App\Http\Controllers\Hero\PortraitController` **(planned)** |
| `InGameBannedClickPage` | Rate limit warning | `App\Livewire\Account\ClickLimitNotice` **(planned)** |
| `InGameBannedPage` | Logged-in ban page | `App\Livewire\Account\BannedNotice` |
| `InGameMaintenanceCtrl` | Maintenance screen | `App\Livewire\System\MaintenanceNotice` |
| `InGameWinnerPage` | World winner announcement | `App\Livewire\World\Winners` **(planned)** |
| `KarteCtrl` | High-resolution map | `App\Livewire\Map\Overview` **(planned)** |
| `KarteCtrlLowRes` | Low-resolution map | `App\Livewire\Map\OverviewLowRes` **(planned)** |
| `LinkListCtrl` | Player link list manager | `App\Livewire\Account\LinkList` **(planned)** |
| `LoginCtrl` | Login form | `App\Http\Controllers\Auth\AuthenticatedSessionController` (Fortify binding) |
| `LogoutCtrl` | Logout endpoint | `App\Http\Controllers\Auth\AuthenticatedSessionController@destroy` (Fortify binding) |
| `ManualCtrl` | Travian manual | `App\Livewire\Content\Manual` **(planned)** |
| `Map_blockCtrl` | Map block metadata | `App\Http\Controllers\Map\TileController` **(planned)** |
| `Map_markCtrl` | Map marker overlay | `App\Livewire\Map\Markers` **(planned)** |
| `MinimapCtrl` | Small map widget | `App\Livewire\Map\MiniMap` **(planned)** |
| `NachrichtenCtrl` | Messages inbox | `App\Livewire\Messages\Inbox` **(planned)** |
| `OnLoadBuildingsDorfCtrl` | Building queue partial | `App\Livewire\Village\BuildingQueue` **(planned)** |
| `OptionCtrl` | Account options | `App\Livewire\Account\Settings` **(planned)** |
| `OutOfGameCtrl` | Out-of-game landing page | `App\Http\Controllers\Marketing\LandingController` **(planned)** |
| `PageNotFoundCtrl` | 404 page | `App\Http\Controllers\Fallback\NotFoundController` **(planned)** |
| `PasswordCtrl` | Password reset | `App\Http\Controllers\Auth\PasswordController` (Fortify binding) |
| `PermissionDeniedCtrl` | Permission denied view | `App\Http\Controllers\Fallback\ForbiddenController` **(planned)** |
| `PositionDetailsCtrl` | Tile detail overlay | `App\Livewire\Map\TileDetails` **(planned)** |
| `ProductionCtrl` | Resource production detail | `App\Livewire\Village\Production` **(planned)** |
| `Quest.php` + `Ajax/questachievements.php` | Quest master overlay showing tutorial/daily quest progress and rewards | `App\Livewire\Game\QuestLog` |
| `PublicMsgCtrl` | World broadcast modal | `App\Livewire\System\PublicMessage` **(planned)** |
| `RecaptchaCtrl` | CAPTCHA verification | `App\Livewire\Account\RecaptchaChallenge` **(planned)** |
| `SpielerCtrl` | Player profile | `App\Livewire\Players\Profile` **(planned)** |
| `StartAdventure` | Hero adventure start | `App\Http\Controllers\Hero\AdventureController` **(planned)** |
| `StatistikenCtrl` | Statistics hub | `App\Livewire\Statistics\Overview` **(planned)** |
| `SupportCtrl` | Support landing | `App\Http\Controllers\Support\IndexController` **(planned)** |
| `SupportFormCtrl` | Support request form | `App\Livewire\Support\TicketForm` **(planned)** |
| `TG_PAY` | Payment provider handshake | `App\Http\Controllers\Payments\GatewayController` **(planned)** |
| `VoucherCtrl` | Voucher redemption | `App\Livewire\Payments\VoucherRedeemer` **(planned)** |
| `WinnerCtrl` | Endgame summary | `App\Livewire\World\WinnerArchive` **(planned)** |

## Build menu controllers

| Legacy class | Laravel target |
| --- | --- |
| `Build\Academy.php` | `App\Livewire\Buildings\Academy` **(planned)** |
| `Build\Armory.php` | `App\Livewire\Buildings\Armory` **(planned)** |
| `Build\Barracks.php` | `App\Livewire\Buildings\Barracks` **(planned)** |
| `Build\Embassy.php` | `App\Livewire\Buildings\Embassy` **(planned)** |
| `Build\Granary.php` | `App\Livewire\Buildings\Granary` **(planned)** |
| `Build\MainBuilding.php` | `App\Livewire\Buildings\MainBuilding` **(planned)** |
| `Build\Marketplace.php` | `App\Livewire\Buildings\Marketplace` **(planned)** |
| `Build\Palace.php` | `App\Livewire\Buildings\Palace` **(planned)** |
| `Build\Residence.php` | `App\Livewire\Buildings\Residence` **(planned)** |
| `Build\Smithy.php` | `App\Livewire\Buildings\Smithy` **(planned)** |
| `Build\Stable.php` | `App\Livewire\Buildings\Stable` **(planned)** |

## Rally point controllers

| Legacy class | Laravel target |
| --- | --- |
| `RallyPoint\Overview.php` | `App\Livewire\RallyPoint\Overview` **(planned)** |
| `RallyPoint\SendTroops.php` | `App\Livewire\RallyPoint\SendTroops` **(planned)** |
| `RallyPoint\Movement.php` | `App\Livewire\RallyPoint\Movements` **(planned)** |
| `RallyPoint\Reinforcements.php` | `App\Livewire\RallyPoint\Reinforcements` **(planned)** |
| `RallyPoint\FarmList.php` | `App\Livewire\RallyPoint\FarmLists` **(planned)** |
| `RallyPoint\CombatSimulator.php` | `App\Livewire\RallyPoint\Simulator` **(planned)** |
| `RallyPoint\HeroSend.php` | `App\Livewire\RallyPoint\HeroDeploy` **(planned)** |

## AJAX endpoints

All controllers inside `Controller/Ajax` translate into Livewire action methods on the components listed above. Each AJAX file has been cataloged for migration and will be converted alongside the parent component that owns the related UI interaction.
