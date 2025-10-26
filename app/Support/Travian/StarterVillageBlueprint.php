<?php

declare(strict_types=1);

namespace App\Support\Travian;

final class StarterVillageBlueprint
{
    /**
     * @var list<string>
     */
    private const RESOURCE_FIELD_PATTERN = [
        'wood', 'wood', 'wood', 'wood',
        'clay', 'clay', 'clay', 'clay',
        'iron', 'iron', 'iron', 'iron',
        'crop', 'crop', 'crop', 'crop', 'crop', 'crop',
    ];

    /**
     * @var list<array{slot: int, gid: int, level: int}>
     */
    private const STARTING_STRUCTURES = [
        ['slot' => 1, 'gid' => 1, 'level' => 1],
        ['slot' => 2, 'gid' => 10, 'level' => 1],
        ['slot' => 3, 'gid' => 11, 'level' => 1],
        ['slot' => 4, 'gid' => 16, 'level' => 1],
    ];

    /**
     * @return list<string>
     */
    public static function resourceFieldPattern(): array
    {
        return self::RESOURCE_FIELD_PATTERN;
    }

    /**
     * @return list<array{slot: int, gid: int, level: int}>
     */
    public static function infrastructure(): array
    {
        return self::STARTING_STRUCTURES;
    }

    /**
     * @return list<array{slot: int, kind: string, level: int}>
     */
    public static function resourceFieldBlueprint(int $cropLevel = 1, int $otherLevel = 0): array
    {
        $fields = [];

        foreach (self::RESOURCE_FIELD_PATTERN as $index => $kind) {
            $fields[] = [
                'slot' => $index + 1,
                'kind' => $kind,
                'level' => $kind === 'crop' ? $cropLevel : $otherLevel,
            ];
        }

        return $fields;
    }

    /**
     * @return array<string, int>
     */
    public static function baseResourceBalances(float $storageMultiplier = 1.0): array
    {
        $baseStorage = self::baseStorageCapacity($storageMultiplier);

        $minimumStock = (int) ceil($baseStorage * 0.9375);

        return [
            'wood' => $minimumStock,
            'clay' => $minimumStock,
            'iron' => $minimumStock,
            'crop' => $minimumStock,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function baseStorage(float $storageMultiplier = 1.0): array
    {
        $baseStorage = self::baseStorageCapacity($storageMultiplier);

        return [
            'wood' => $baseStorage,
            'clay' => $baseStorage,
            'iron' => $baseStorage,
            'crop' => $baseStorage,
            'warehouse' => $baseStorage,
            'granary' => $baseStorage,
            'extra' => [
                'warehouse' => 0,
                'granary' => 0,
            ],
        ];
    }

    /**
     * @return array<string, int>
     */
    public static function baseProduction(): array
    {
        return [
            'wood' => 2,
            'clay' => 2,
            'iron' => 2,
            'crop' => 2,
        ];
    }

    private static function baseStorageCapacity(float $storageMultiplier): int
    {
        $multiplier = max(0.1, $storageMultiplier);

        return (int) round(800 * $multiplier);
    }
}
