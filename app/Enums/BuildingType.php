<?php

declare(strict_types=1);

namespace App\Enums;

enum BuildingType: int
{
    case WOODCUTTER = 1;
    case CLAY_PIT = 2;
    case IRON_MINE = 3;
    case CROPLAND = 4;
    case SAWMILL = 5;
    case BRICKYARD = 6;
    case IRON_FOUNDRY = 7;
    case GRAIN_MILL = 8;
    case BAKERY = 9;
    case WAREHOUSE = 10;
    case GRANARY = 11;
    case BLACKSMITH = 12;
    case SMITHY = 13;
    case TOURNAMENT_SQUARE = 14;
    case MAIN_BUILDING = 15;
    case RALLY_POINT = 16;
    case MARKETPLACE = 17;
    case EMBASSY = 18;
    case BARRACKS = 19;
    case STABLE = 20;
    case WORKSHOP = 21;
    case ACADEMY = 22;
    case CRANNY = 23;
    case TOWN_HALL = 24;
    case RESIDENCE = 25;
    case PALACE = 26;
    case TREASURY = 27;
    case TRADE_OFFICE = 28;
    case GREAT_BARRACKS = 29;
    case GREAT_STABLE = 30;
    case CITY_WALL = 31;
    case EARTH_WALL = 32;
    case PALISADE = 33;
    case STONEMASONS_LODGE = 34;
    case BREWERY = 35;
    case TRAPPER = 36;
    case HEROS_MANSION = 37;
    case GREAT_WAREHOUSE = 38;
    case GREAT_GRANARY = 39;
    case WONDER_OF_THE_WORLD = 40;
    case HORSE_DRINKING_TROUGH = 41;
    case STONE_WALL = 42;
    case MAKESHIFT_WALL = 43;
    case COMMAND_CENTER = 44;
    case WATERWORKS = 45;

    private const LABELS = [
        self::WOODCUTTER->value => 'Woodcutter',
        self::CLAY_PIT->value => 'Clay Pit',
        self::IRON_MINE->value => 'Iron Mine',
        self::CROPLAND->value => 'Cropland',
        self::SAWMILL->value => 'Sawmill',
        self::BRICKYARD->value => 'Brickyard',
        self::IRON_FOUNDRY->value => 'Iron Foundry',
        self::GRAIN_MILL->value => 'Grain Mill',
        self::BAKERY->value => 'Bakery',
        self::WAREHOUSE->value => 'Warehouse',
        self::GRANARY->value => 'Granary',
        self::BLACKSMITH->value => 'Blacksmith',
        self::SMITHY->value => 'Smithy',
        self::TOURNAMENT_SQUARE->value => 'Tournament Square',
        self::MAIN_BUILDING->value => 'Main Building',
        self::RALLY_POINT->value => 'Rally Point',
        self::MARKETPLACE->value => 'Marketplace',
        self::EMBASSY->value => 'Embassy',
        self::BARRACKS->value => 'Barracks',
        self::STABLE->value => 'Stable',
        self::WORKSHOP->value => 'Workshop',
        self::ACADEMY->value => 'Academy',
        self::CRANNY->value => 'Cranny',
        self::TOWN_HALL->value => 'Town Hall',
        self::RESIDENCE->value => 'Residence',
        self::PALACE->value => 'Palace',
        self::TREASURY->value => 'Treasury',
        self::TRADE_OFFICE->value => 'Trade Office',
        self::GREAT_BARRACKS->value => 'Great Barracks',
        self::GREAT_STABLE->value => 'Great Stable',
        self::CITY_WALL->value => 'City Wall',
        self::EARTH_WALL->value => 'Earth Wall',
        self::PALISADE->value => 'Palisade',
        self::STONEMASONS_LODGE->value => "Stonemason's Lodge",
        self::BREWERY->value => 'Brewery',
        self::TRAPPER->value => 'Trapper',
        self::HEROS_MANSION->value => "Hero's Mansion",
        self::GREAT_WAREHOUSE->value => 'Great Warehouse',
        self::GREAT_GRANARY->value => 'Great Granary',
        self::WONDER_OF_THE_WORLD->value => 'Wonder of the World',
        self::HORSE_DRINKING_TROUGH->value => 'Horse Drinking Trough',
        self::STONE_WALL->value => 'Stone Wall',
        self::MAKESHIFT_WALL->value => 'Makeshift Wall',
        self::COMMAND_CENTER->value => 'Command Center',
        self::WATERWORKS->value => 'Waterworks',
    ];

    public function label(): string
    {
        return self::LABELS[$this->value];
    }

    public function slug(): string
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $this->label()));

        return trim($slug, '_');
    }

    public function isResourceField(): bool
    {
        return match ($this) {
            self::WOODCUTTER,
            self::CLAY_PIT,
            self::IRON_MINE,
            self::CROPLAND => true,
            default => false,
        };
    }

    public function isGreatBuilding(): bool
    {
        return match ($this) {
            self::GREAT_BARRACKS,
            self::GREAT_STABLE,
            self::GREAT_WAREHOUSE,
            self::GREAT_GRANARY,
            self::WONDER_OF_THE_WORLD => true,
            default => false,
        };
    }

    public static function tryFromName(string $name): ?self
    {
        $normalized = strtolower(trim($name));

        foreach (self::cases() as $type) {
            if (strtolower($type->label()) === $normalized) {
                return $type;
            }
        }

        return null;
    }

    public static function tryFromSlug(string $slug): ?self
    {
        $normalized = strtolower(trim($slug));

        foreach (self::cases() as $type) {
            if ($type->slug() === $normalized) {
                return $type;
            }
        }

        return null;
    }

    /**
     * @return list<self>
     */
    public static function resourceFields(): array
    {
        return [
            self::WOODCUTTER,
            self::CLAY_PIT,
            self::IRON_MINE,
            self::CROPLAND,
        ];
    }
}
