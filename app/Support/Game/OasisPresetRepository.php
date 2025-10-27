<?php

declare(strict_types=1);

namespace App\Support\Game;

use Illuminate\Support\Arr;

/**
 * The OasisPresetRepository centralises the default nature garrison
 * compositions and their respective respawn timings so both the
 * world seeder and the respawn job stay in sync.
 */
class OasisPresetRepository
{
    /**
     * @var array<int, array{label: string, garrison: array<string, int>, respawn_minutes: int}>
     */
    private const PRESETS = [
        1 => [
            'label' => 'wood',
            'garrison' => [
                'rat' => 25,
                'spider' => 18,
                'boar' => 12,
                'wolf' => 5,
                'bear' => 2,
            ],
            'respawn_minutes' => 180,
        ],
        2 => [
            'label' => 'clay',
            'garrison' => [
                'rat' => 22,
                'spider' => 20,
                'snake' => 18,
                'bat' => 10,
                'boar' => 5,
            ],
            'respawn_minutes' => 240,
        ],
        3 => [
            'label' => 'iron',
            'garrison' => [
                'rat' => 18,
                'spider' => 16,
                'snake' => 20,
                'bat' => 16,
                'bear' => 12,
                'crocodile' => 8,
            ],
            'respawn_minutes' => 300,
        ],
        4 => [
            'label' => 'crop',
            'garrison' => [
                'rat' => 20,
                'spider' => 20,
                'boar' => 24,
                'wolf' => 20,
                'bear' => 18,
                'tiger' => 12,
                'elephant' => 6,
            ],
            'respawn_minutes' => 360,
        ],
    ];

    /**
     * Retrieve every preset keyed by oasis type so callers can iterate over
     * the full configuration when seeding or reporting.
     *
     * @return array<int, array{label: string, garrison: array<string, int>, respawn_minutes: int}>
     */
    public static function all(): array
    {
        return self::PRESETS;
    }

    /**
     * Provide the display labels keyed by type for quick lookups when
     * aggregating map statistics.
     *
     * @return array<int, string>
     */
    public static function labels(): array
    {
        return Arr::pluck(self::PRESETS, 'label');
    }

    /**
     * Resolve the garrison composition for a given oasis type.
     *
     * @return array<string, int>
     */
    public static function garrisonForType(int $type): array
    {
        $preset = self::PRESETS[$type] ?? null;

        if ($preset === null) {
            return [];
        }

        return array_map(static fn (int $count): int => (int) $count, $preset['garrison']);
    }

    /**
     * Fetch the configured respawn duration in minutes for the supplied type.
     */
    public static function respawnMinutesForType(int $type): ?int
    {
        $preset = self::PRESETS[$type] ?? null;

        if ($preset === null) {
            return null;
        }

        return (int) $preset['respawn_minutes'];
    }

    /**
     * Locate the label for a specific oasis type to aid with reporting.
     */
    public static function labelForType(int $type): ?string
    {
        $preset = self::PRESETS[$type] ?? null;

        if ($preset === null) {
            return null;
        }

        return $preset['label'];
    }
}

