<?php

namespace App\Services\Game;

class LegacyModelCatalogue
{
    /**
     * @return array<string, array{responsibility: string, service: string, models: list<string>}>
     */
    public function all(): array
    {
        return self::CATALOGUE;
    }

    private const CATALOGUE = [
        'AccountDeleter' => [
            'responsibility' => 'Schedules account deletions and frees associated villages/resources.',
            'service' => App\Services\Game\AccountLifecycleService::class,
            'models' => ['App\Models\Game\UserAccount', 'App\Models\Game\LegacyVillage'],
        ],
        'AdventureModel' => [
            'responsibility' => 'Creates starter adventures and manages hero adventure state.',
            'service' => App\Services\Game\AdventureService::class,
            'models' => ['App\Models\Game\Adventure', 'App\Models\Game\Hero'],
        ],
        'AllianceBonusModel' => [
            'responsibility' => 'Calculates and applies alliance wide bonus effects.',
            'service' => App\Services\Game\AllianceService::class,
            'models' => ['App\Models\Game\Summary'],
        ],
        'AllianceModel' => [
            'responsibility' => 'CRUD for alliances, membership, diplomacy, and ranks.',
            'service' => App\Services\Game\AllianceService::class,
            'models' => ['App\Models\Game\Summary'],
        ],
        'ArtefactsModel' => [
            'responsibility' => 'Spawns and assigns unique artifacts across the map.',
            'service' => App\Services\Game\ArtefactService::class,
            'models' => ['App\Models\Game\AvailableVillage', 'App\Models\Game\MapTile'],
        ],
        'AuctionModel' => [
            'responsibility' => 'Manages hero auction listings, bids, and settlements.',
            'service' => App\Services\Game\AuctionService::class,
            'models' => ['App\Models\Game\HeroInventory'],
        ],
        'AutoExtendModel' => [
            'responsibility' => 'Extends premium account and plus feature timers.',
            'service' => App\Services\Game\PremiumFeatureService::class,
            'models' => ['App\Models\Game\UserAccount'],
        ],
        'AutomationModel' => [
            'responsibility' => 'Runs scheduled legacy automation tasks and cron jobs.',
            'service' => App\Services\Game\AutomationService::class,
            'models' => ['App\Models\Game\ServerTask'],
        ],
        'BattleModel' => [
            'responsibility' => 'Legacy battle calculations, outcome persistence, and clean-up.',
            'service' => App\Services\Game\BattleService::class,
            'models' => ['App\Models\Game\Movement', 'App\Models\Game\MovementOrder'],
        ],
        'BattleNew' => [
            'responsibility' => 'Newer battle engine orchestrator for mid-round simulation.',
            'service' => App\Services\Game\BattleService::class,
            'models' => ['App\Models\Game\Movement'],
        ],
        'BattleSetter' => [
            'responsibility' => 'Queues and prepares battle simulations from movement orders.',
            'service' => App\Services\Game\BattleService::class,
            'models' => ['App\Models\Game\MovementOrder'],
        ],
        'BerichteModel' => [
            'responsibility' => 'Generates battle and event reports for delivery.',
            'service' => App\Services\Game\ReportService::class,
            'models' => ['App\Models\Game\Movement'],
        ],
        'CasualtiesModel' => [
            'responsibility' => 'Persists troop casualty logs and healing information.',
            'service' => App\Services\Game\CasualtyService::class,
            'models' => ['App\Models\Game\UnitStack'],
        ],
        'ClubApi' => [
            'responsibility' => 'Handles Travian club API communication and callbacks.',
            'service' => App\Services\Game\ClubApiService::class,
            'models' => [],
        ],
        'CropFinderModel' => [
            'responsibility' => 'Searches map data for optimal crop villages.',
            'service' => App\Services\Game\CropFinderService::class,
            'models' => ['App\Models\Game\AvailableVillage', 'App\Models\Game\MapTile'],
        ],
        'DailyQuestModel' => [
            'responsibility' => 'Legacy implementation for daily quest tracking and rewards.',
            'service' => App\Services\Game\DailyQuestService::class,
            'models' => ['App\Models\Game\DailyQuestProgress'],
        ],
        'Dorf1Model' => [
            'responsibility' => 'Builds data for the main village overview screen.',
            'service' => App\Services\Game\VillageOverviewService::class,
            'models' => ['App\Models\Game\LegacyVillage'],
        ],
        'EmailVerification' => [
            'responsibility' => 'Manages email verification tokens and confirmation.',
            'service' => App\Services\Game\AccountVerificationService::class,
            'models' => ['App\Models\Game\UserAccount'],
        ],
        'FarmListModel' => [
            'responsibility' => 'Creates and executes raid/farm lists.',
            'service' => App\Services\Game\FarmListService::class,
            'models' => ['App\Models\Game\Movement'],
        ],
        'FakeUserModel' => [
            'responsibility' => 'Seeds fake user accounts for balancing or testing.',
            'service' => App\Services\Game\FakeUserService::class,
            'models' => ['App\Models\Game\UserAccount'],
        ],
        'ForumModel' => [
            'responsibility' => 'Legacy alliance forum management.',
            'service' => App\Services\Game\AllianceService::class,
            'models' => [],
        ],
        'HeroFaceModel' => [
            'responsibility' => 'Generates random hero appearance attributes.',
            'service' => App\Services\Game\HeroService::class,
            'models' => ['App\Models\Game\HeroAppearance'],
        ],
        'HeroModel' => [
            'responsibility' => 'Stores hero statistics, health, and adventure state.',
            'service' => App\Services\Game\HeroService::class,
            'models' => ['App\Models\Game\Hero'],
        ],
        'InfoBoxModel' => [
            'responsibility' => 'Persists info box notifications and timers.',
            'service' => App\Services\Game\InfoBoxService::class,
            'models' => ['App\Models\Game\InfoBoxEntry'],
        ],
        'InstallerModel' => [
            'responsibility' => 'Initialises a server world and seeds base datasets.',
            'service' => App\Services\Game\InstallerService::class,
            'models' => ['App\Models\Game\AvailableVillage', 'App\Models\Game\MapTile'],
        ],
        'KarteModel' => [
            'responsibility' => 'Provides minimap rendering helpers.',
            'service' => App\Services\Game\MapService::class,
            'models' => ['App\Models\Game\MapTile'],
        ],
        'LinksModel' => [
            'responsibility' => 'Maintains curated link lists for the UI.',
            'service' => App\Services\Game\LinkService::class,
            'models' => [],
        ],
        'LoginModel' => [
            'responsibility' => 'Legacy login helper utilities.',
            'service' => App\Services\Game\LoginService::class,
            'models' => ['App\Models\Game\UserAccount'],
        ],
        'MapModel' => [
            'responsibility' => 'Wraps world map queries and coordinate helpers.',
            'service' => App\Services\Game\MapService::class,
            'models' => ['App\Models\Game\MapTile'],
        ],
        'MarketModel' => [
            'responsibility' => 'Manages market offers and merchant dispatch.',
            'service' => App\Services\Game\MarketService::class,
            'models' => ['App\Models\Game\LegacyVillage'],
        ],
        'MarketPlaceProcessor' => [
            'responsibility' => 'Processes accepted marketplace trades.',
            'service' => App\Services\Game\MarketService::class,
            'models' => ['App\Models\Game\LegacyVillage'],
        ],
        'MasterBuilder' => [
            'responsibility' => 'Handles master builder construction queues.',
            'service' => App\Services\Game\MasterBuilderService::class,
            'models' => ['App\Models\Game\VillageBuildingUpgrade'],
        ],
        'MedalsModel' => [
            'responsibility' => 'Distributes medals and weekly achievement rewards.',
            'service' => App\Services\Game\MedalService::class,
            'models' => ['App\Models\Game\Summary'],
        ],
        'MessageModel' => [
            'responsibility' => 'Stores and retrieves in-game messages.',
            'service' => App\Services\Game\MessageService::class,
            'models' => ['App\Models\Game\UserAccount'],
        ],
        'Movements\AdventureProcessor' => [
            'responsibility' => 'Resolves hero adventure movements.',
            'service' => App\Services\Game\MovementProcessingService::class,
            'models' => ['App\Models\Game\Movement'],
        ],
        'Movements\EvasionProcessor' => [
            'responsibility' => 'Handles evasion logic for troop movements.',
            'service' => App\Services\Game\MovementProcessingService::class,
            'models' => ['App\Models\Game\Movement'],
        ],
        'Movements\ReinforcementProcessor' => [
            'responsibility' => 'Processes reinforcement movements and arrivals.',
            'service' => App\Services\Game\MovementProcessingService::class,
            'models' => ['App\Models\Game\Movement'],
        ],
        'Movements\ReturnProcessor' => [
            'responsibility' => 'Handles returning troops and raid haul bookkeeping.',
            'service' => App\Services\Game\MovementProcessingService::class,
            'models' => ['App\Models\Game\Movement'],
        ],
        'Movements\SettlersProcessor' => [
            'responsibility' => 'Executes settler colonisation attempts.',
            'service' => App\Services\Game\MovementProcessingService::class,
            'models' => ['App\Models\Game\Movement'],
        ],
        'MovementsModel' => [
            'responsibility' => 'General movement repository for attacks and reinforcements.',
            'service' => App\Services\Game\MovementProcessingService::class,
            'models' => ['App\Models\Game\Movement'],
        ],
        'MultiAccount' => [
            'responsibility' => 'Detects linked accounts for moderation review.',
            'service' => App\Services\Game\MultiAccountService::class,
            'models' => ['App\Models\Game\UserAccount'],
        ],
        'NatarsModel' => [
            'responsibility' => 'Controls Natar AI villages and attacks.',
            'service' => App\Services\Game\NatarsService::class,
            'models' => ['App\Models\Game\AvailableVillage'],
        ],
        'NewsModel' => [
            'responsibility' => 'Publishes server news entries.',
            'service' => App\Services\Game\NewsService::class,
            'models' => [],
        ],
        'OasesModel' => [
            'responsibility' => 'Manages oasis resource ticks and ownership.',
            'service' => App\Services\Game\OasisService::class,
            'models' => ['App\Models\Game\Oasis'],
        ],
        'OptionModel' => [
            'responsibility' => 'Persists player preferences, vacation and deletion flags.',
            'service' => App\Services\Game\OptionService::class,
            'models' => ['App\Models\Game\UserAccount'],
        ],
        'PlayerModel' => [
            'responsibility' => 'Provides player level statistics and rankings.',
            'service' => App\Services\Game\PlayerService::class,
            'models' => ['App\Models\Game\UserAccount'],
        ],
        'PlayerNote' => [
            'responsibility' => 'Stores notes players leave on other accounts.',
            'service' => App\Services\Game\PlayerNotesService::class,
            'models' => ['App\Models\Game\UserAccount'],
        ],
        'ProfileModel' => [
            'responsibility' => 'Maintains player profile descriptions and metadata.',
            'service' => App\Services\Game\ProfileService::class,
            'models' => ['App\Models\Game\UserAccount'],
        ],
        'PublicMsgModel' => [
            'responsibility' => 'Handles public messaging board posts.',
            'service' => App\Services\Game\PublicMessageService::class,
            'models' => [],
        ],
        'Quest' => [
            'responsibility' => 'Legacy quest tracking and tutorial guidance.',
            'service' => App\Services\Game\QuestService::class,
            'models' => ['App\Models\Game\DailyQuestProgress'],
        ],
        'RegisterModel' => [
            'responsibility' => 'Creates player accounts, base villages, and starter heroes.',
            'service' => App\Services\Game\RegistrationService::class,
            'models' => ['App\Models\Game\UserAccount', 'App\Models\Game\AvailableVillage', 'App\Models\Game\LegacyVillage'],
        ],
        'RallyPoint\RallyPointModel' => [
            'responsibility' => 'Provides rally point command data and presets.',
            'service' => App\Services\Game\RallyPointService::class,
            'models' => ['App\Models\Game\LegacyVillage'],
        ],
        'StatisticsModel' => [
            'responsibility' => 'Aggregates statistics for leaderboards.',
            'service' => App\Services\Game\StatisticsService::class,
            'models' => ['App\Models\Game\Summary'],
        ],
        'SummaryModel' => [
            'responsibility' => 'Caches world summary metrics such as tribe counts.',
            'service' => App\Services\Game\SummaryService::class,
            'models' => ['App\Models\Game\Summary'],
        ],
        'TaskQueue' => [
            'responsibility' => 'Legacy task queue driver and dispatcher.',
            'service' => App\Services\Game\TaskQueueService::class,
            'models' => ['App\Models\Game\ServerTask'],
        ],
        'TrainingModel' => [
            'responsibility' => 'Handles troop training queues and timers.',
            'service' => App\Services\Game\TrainingQueueService::class,
            'models' => ['App\Models\Game\UnitTrainingBatch'],
        ],
        'TransferGoldModel' => [
            'responsibility' => 'Processes gold transfers and purchases.',
            'service' => App\Services\Game\TransferGoldService::class,
            'models' => ['App\Models\Game\UserAccount'],
        ],
        'Units' => [
            'responsibility' => 'Defines troop metadata and upkeep calculations.',
            'service' => App\Services\Game\UnitService::class,
            'models' => ['App\Models\Game\UnitStack'],
        ],
        'VillageModel' => [
            'responsibility' => 'Legacy village operations including loyalty, expansion, and resource setup.',
            'service' => App\Services\Game\VillageService::class,
            'models' => ['App\Models\Game\LegacyVillage', 'App\Models\Game\VillageFieldLayout'],
        ],
        'VillageOverviewModel' => [
            'responsibility' => 'Legacy implementation of the village overview lists.',
            'service' => App\Services\Game\VillageOverviewService::class,
            'models' => ['App\Models\Game\LegacyVillage'],
        ],
        'WonderOfTheWorldModel' => [
            'responsibility' => 'Wonder of the World progress tracking and scoring.',
            'service' => App\Services\Game\WonderOfTheWorldService::class,
            'models' => ['App\Models\Game\LegacyVillage'],
        ],
        'inactiveModel' => [
            'responsibility' => 'Handles inactivity sweeps and vacation cancellation.',
            'service' => App\Services\Game\AccountLifecycleService::class,
            'models' => ['App\Models\Game\UserAccount'],
        ],
    ];
}
