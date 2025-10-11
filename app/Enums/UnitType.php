<?php

declare(strict_types=1);

namespace App\Enums;

enum UnitType: int
{
    case ROMAN_LEGIONNAIRE = 1;
    case ROMAN_PRAETORIAN = 2;
    case ROMAN_IMPERIAN = 3;
    case ROMAN_EQUITES_LEGATI = 4;
    case ROMAN_EQUITES_IMPERATORIS = 5;
    case ROMAN_EQUITES_CAESARIS = 6;
    case ROMAN_BATTERING_RAM = 7;
    case ROMAN_FIRE_CATAPULT = 8;
    case ROMAN_SENATOR = 9;
    case ROMAN_SETTLER = 10;
    case TEUTON_CLUBSWINGER = 11;
    case TEUTON_SPEARMAN = 12;
    case TEUTON_AXEMAN = 13;
    case TEUTON_SCOUT = 14;
    case TEUTON_PALADIN = 15;
    case TEUTON_TEUTONIC_KNIGHT = 16;
    case TEUTON_RAM = 17;
    case TEUTON_CATAPULT = 18;
    case TEUTON_CHIEF = 19;
    case TEUTON_SETTLER = 20;
    case GAUL_PHALANX = 21;
    case GAUL_SWORDSMAN = 22;
    case GAUL_PATHFINDER = 23;
    case GAUL_THEUTATES_THUNDER = 24;
    case GAUL_DRUIDRIDER = 25;
    case GAUL_HAEDUAN = 26;
    case GAUL_RAM = 27;
    case GAUL_CATAPULT = 28;
    case GAUL_CHIEFTAIN = 29;
    case GAUL_SETTLER = 30;
    case NATURE_RAT = 31;
    case NATURE_SPIDER = 32;
    case NATURE_SNAKE = 33;
    case NATURE_BAT = 34;
    case NATURE_WILD_BOAR = 35;
    case NATURE_WOLF = 36;
    case NATURE_BEAR = 37;
    case NATURE_CROCODILE = 38;
    case NATURE_TIGER = 39;
    case NATURE_ELEPHANT = 40;
    case NATAR_PIKEMAN = 41;
    case NATAR_THORNED_WARRIOR = 42;
    case NATAR_GUARDSMAN = 43;
    case NATAR_BIRDS_OF_PREY = 44;
    case NATAR_AXERIDER = 45;
    case NATAR_NATARIAN_KNIGHT = 46;
    case NATAR_WAR_ELEPHANT = 47;
    case NATAR_BALLISTA = 48;
    case NATAR_NATARIAN_EMPEROR = 49;
    case NATAR_SETTLER = 50;
    case EGYPTIAN_SLAVE_MILITIA = 51;
    case EGYPTIAN_ASH_WARDEN = 52;
    case EGYPTIAN_KHOPESH_WARRIOR = 53;
    case EGYPTIAN_SOPDU_EXPLORER = 54;
    case EGYPTIAN_ANHUR_GUARD = 55;
    case EGYPTIAN_RESHEPH_CHARIOT = 56;
    case EGYPTIAN_RAM = 57;
    case EGYPTIAN_STONE_CATAPULT = 58;
    case EGYPTIAN_NOMARCH = 59;
    case EGYPTIAN_SETTLER = 60;
    case HUN_MERCENARY = 61;
    case HUN_BOWMAN = 62;
    case HUN_SPOTTER = 63;
    case HUN_STEPPE_RIDER = 64;
    case HUN_MARKSMAN = 65;
    case HUN_MARAUDER = 66;
    case HUN_RAM = 67;
    case HUN_CATAPULT = 68;
    case HUN_LOGADES = 69;
    case HUN_SETTLER = 70;
    case HERO = 98;
    case TRAP = 99;

    private const LABELS = [
        self::ROMAN_LEGIONNAIRE->value => 'Legionnaire',
        self::ROMAN_PRAETORIAN->value => 'Praetorian',
        self::ROMAN_IMPERIAN->value => 'Imperian',
        self::ROMAN_EQUITES_LEGATI->value => 'Equites Legati',
        self::ROMAN_EQUITES_IMPERATORIS->value => 'Equites Imperatoris',
        self::ROMAN_EQUITES_CAESARIS->value => 'Equites Caesaris',
        self::ROMAN_BATTERING_RAM->value => 'Battering Ram',
        self::ROMAN_FIRE_CATAPULT->value => 'Fire Catapult',
        self::ROMAN_SENATOR->value => 'Senator',
        self::ROMAN_SETTLER->value => 'Settler',
        self::TEUTON_CLUBSWINGER->value => 'Clubswinger',
        self::TEUTON_SPEARMAN->value => 'Spearman',
        self::TEUTON_AXEMAN->value => 'Axeman',
        self::TEUTON_SCOUT->value => 'Scout',
        self::TEUTON_PALADIN->value => 'Paladin',
        self::TEUTON_TEUTONIC_KNIGHT->value => 'Teutonic Knight',
        self::TEUTON_RAM->value => 'Ram',
        self::TEUTON_CATAPULT->value => 'Catapult',
        self::TEUTON_CHIEF->value => 'Chief',
        self::TEUTON_SETTLER->value => 'Settler',
        self::GAUL_PHALANX->value => 'Phalanx',
        self::GAUL_SWORDSMAN->value => 'Swordsman',
        self::GAUL_PATHFINDER->value => 'Pathfinder',
        self::GAUL_THEUTATES_THUNDER->value => 'Theutates Thunder',
        self::GAUL_DRUIDRIDER->value => 'Druidrider',
        self::GAUL_HAEDUAN->value => 'Haeduan',
        self::GAUL_RAM->value => 'Ram',
        self::GAUL_CATAPULT->value => 'Catapult',
        self::GAUL_CHIEFTAIN->value => 'Chieftain',
        self::GAUL_SETTLER->value => 'Settler',
        self::NATURE_RAT->value => 'Rat',
        self::NATURE_SPIDER->value => 'Spider',
        self::NATURE_SNAKE->value => 'Snake',
        self::NATURE_BAT->value => 'Bat',
        self::NATURE_WILD_BOAR->value => 'Wild Boar',
        self::NATURE_WOLF->value => 'Wolf',
        self::NATURE_BEAR->value => 'Bear',
        self::NATURE_CROCODILE->value => 'Crocodile',
        self::NATURE_TIGER->value => 'Tiger',
        self::NATURE_ELEPHANT->value => 'Elephant',
        self::NATAR_PIKEMAN->value => 'Pikeman',
        self::NATAR_THORNED_WARRIOR->value => 'Thorned Warrior',
        self::NATAR_GUARDSMAN->value => 'Guardsman',
        self::NATAR_BIRDS_OF_PREY->value => 'Birds Of Prey',
        self::NATAR_AXERIDER->value => 'Axerider',
        self::NATAR_NATARIAN_KNIGHT->value => 'Natarian Knight',
        self::NATAR_WAR_ELEPHANT->value => 'War Elephant',
        self::NATAR_BALLISTA->value => 'Ballista',
        self::NATAR_NATARIAN_EMPEROR->value => 'Natarian Emperor',
        self::NATAR_SETTLER->value => 'Settler',
        self::EGYPTIAN_SLAVE_MILITIA->value => 'Slave Militia',
        self::EGYPTIAN_ASH_WARDEN->value => 'Ash Warden',
        self::EGYPTIAN_KHOPESH_WARRIOR->value => 'Khopesh Warrior',
        self::EGYPTIAN_SOPDU_EXPLORER->value => 'Sopdu Explorer',
        self::EGYPTIAN_ANHUR_GUARD->value => 'Anhur Guard',
        self::EGYPTIAN_RESHEPH_CHARIOT->value => 'Resheph Chariot',
        self::EGYPTIAN_RAM->value => 'Ram',
        self::EGYPTIAN_STONE_CATAPULT->value => 'Stone Catapult',
        self::EGYPTIAN_NOMARCH->value => 'Nomarch',
        self::EGYPTIAN_SETTLER->value => 'Settler',
        self::HUN_MERCENARY->value => 'Mercenary',
        self::HUN_BOWMAN->value => 'Bowman',
        self::HUN_SPOTTER->value => 'Spotter',
        self::HUN_STEPPE_RIDER->value => 'Steppe Rider',
        self::HUN_MARKSMAN->value => 'Marksman',
        self::HUN_MARAUDER->value => 'Marauder',
        self::HUN_RAM->value => 'Ram',
        self::HUN_CATAPULT->value => 'Catapult',
        self::HUN_LOGADES->value => 'Logades',
        self::HUN_SETTLER->value => 'Settler',
        self::HERO->value => 'Hero',
        self::TRAP->value => 'Trap',
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

    public function tribe(): string
    {
        return match (true) {
            $this->value >= 1 && $this->value <= 10 => 'roman',
            $this->value >= 11 && $this->value <= 20 => 'teuton',
            $this->value >= 21 && $this->value <= 30 => 'gaul',
            $this->value >= 31 && $this->value <= 40 => 'nature',
            $this->value >= 41 && $this->value <= 50 => 'natar',
            $this->value >= 51 && $this->value <= 60 => 'egyptian',
            $this->value >= 61 && $this->value <= 70 => 'hun',
            $this === self::HERO => 'universal',
            $this === self::TRAP => 'natar',
        };
    }

    public function race(): ?int
    {
        return match (true) {
            $this->value >= 1 && $this->value <= 10 => 0,
            $this->value >= 11 && $this->value <= 20 => 1,
            $this->value >= 21 && $this->value <= 30 => 2,
            $this->value >= 31 && $this->value <= 40 => 3,
            $this->value >= 41 && $this->value <= 50 => 4,
            $this->value >= 51 && $this->value <= 60 => 5,
            $this->value >= 61 && $this->value <= 70 => 6,
            $this === self::TRAP => 4,
            default => null,
        };
    }

    public function slot(): ?int
    {
        if ($this === self::TRAP) {
            return 99;
        }

        $race = $this->race();
        if ($race === null) {
            return null;
        }

        return $this->value - ($race * 10) - 1;
    }

    public function isSettler(): bool
    {
        return match ($this) {
            self::ROMAN_SETTLER,
            self::TEUTON_SETTLER,
            self::GAUL_SETTLER,
            self::NATAR_SETTLER,
            self::EGYPTIAN_SETTLER,
            self::HUN_SETTLER => true,
            default => false,
        };
    }

    public function isHero(): bool
    {
        return $this === self::HERO;
    }

    public function isTrap(): bool
    {
        return $this === self::TRAP;
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

    public static function tryFromIdentifier(string $identifier): ?self
    {
        $identifier = strtolower(trim($identifier));

        if ($identifier === '') {
            return null;
        }

        if ($identifier[0] === 'u') {
            $numeric = (int) substr($identifier, 1);

            return self::tryFrom($numeric);
        }

        return self::tryFromName($identifier);
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

    public static function fromRaceAndSlot(int $race, int $slot): ?self
    {
        if ($slot === 99) {
            return self::TRAP;
        }

        if ($slot < 0) {
            return null;
        }

        $unitId = ($race * 10) + ($slot + 1);

        return self::tryFrom($unitId);
    }
}
