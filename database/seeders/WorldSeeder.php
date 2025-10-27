<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Game\World;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WorldSeeder extends Seeder
{
    public const OASIS_PRESETS = [
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

    private const MAP_MIN_COORDINATE = -200;

    private const MAP_MAX_COORDINATE = 200;

    private const TILE_TYPES = [
        '4-4-4-6' => ['id' => 3],
        '9c' => ['id' => 1],
        '15c' => ['id' => 6],
    ];

    private const OASIS_FREQUENCY = 25;

    /**
     * Seed the canonical Travian world with deterministic tile and oasis distribution.
     */
    public function run(): void
    {
        $world = World::query()->firstOrNew(['id' => 1]);

        if ($world->exists === false) {
            $world->setAttribute($world->getKeyName(), 1);
        }

        $world->forceFill([
            'name' => 'World #1',
            'speed' => 1.0,
            'features' => [],
            'starts_at' => Carbon::now()->subDay(),
            'status' => 'active',
        ])->save();

        $world->refresh();

        $worldIdentifier = sprintf('world-%d', $world->getKey());

        $bounds = [
            'min_x' => self::MAP_MIN_COORDINATE,
            'max_x' => self::MAP_MAX_COORDINATE,
            'min_y' => self::MAP_MIN_COORDINATE,
            'max_y' => self::MAP_MAX_COORDINATE,
        ];

        $tileCounts = array_fill_keys(array_keys(self::TILE_TYPES), 0);
        $oasisCounts = [
            'total' => 0,
            'by_type' => array_fill_keys(array_column(self::OASIS_PRESETS, 'label'), 0),
        ];

        $totalTiles = ($bounds['max_x'] - $bounds['min_x'] + 1) * ($bounds['max_y'] - $bounds['min_y'] + 1);
        $oasisTypeIds = array_keys(self::OASIS_PRESETS);
        $tileBatch = [];
        $oasisBatch = [];
        $now = Carbon::now();

        DB::transaction(function () use ($world, $worldIdentifier, $bounds, $oasisTypeIds, $now, &$tileCounts, &$oasisCounts, &$tileBatch, &$oasisBatch): void {
            DB::table('map_tiles')->where('world_id', $worldIdentifier)->delete();
            DB::table('oases')->where('world_id', $world->getKey())->delete();

            $tileBatchSize = 1000;

            for ($y = $bounds['min_y']; $y <= $bounds['max_y']; $y++) {
                for ($x = $bounds['min_x']; $x <= $bounds['max_x']; $x++) {
                    $seed = hexdec(substr(md5($x.'|'.$y), 0, 8));
                    $isOasis = ($seed % self::OASIS_FREQUENCY) === 0;

                    if ($isOasis) {
                        $oasisTypeId = $oasisTypeIds[$seed % count($oasisTypeIds)];
                        $preset = self::OASIS_PRESETS[$oasisTypeId];
                        $oasisLabel = $preset['label'];

                        $tileBatch[] = [
                            'world_id' => $worldIdentifier,
                            'x' => $x,
                            'y' => $y,
                            'tile_type' => 0,
                            'resource_pattern' => 'oasis',
                            'oasis_type' => $oasisTypeId,
                        ];

                        $oasisBatch[] = [
                            'world_id' => $world->getKey(),
                            'x' => $x,
                            'y' => $y,
                            'type' => $oasisTypeId,
                            'nature_garrison' => json_encode($preset['garrison'], JSON_THROW_ON_ERROR),
                            'respawn_at' => $this->calculateRespawnAt($now, (float) $world->speed, $oasisTypeId),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        $oasisCounts['total']++;
                        $oasisCounts['by_type'][$oasisLabel]++;
                    } else {
                        $tileRoll = $seed % 100;

                        $pattern = '4-4-4-6';

                        if ($tileRoll < 10) {
                            $pattern = '15c';
                        } elseif ($tileRoll < 30) {
                            $pattern = '9c';
                        }

                        $tileBatch[] = [
                            'world_id' => $worldIdentifier,
                            'x' => $x,
                            'y' => $y,
                            'tile_type' => self::TILE_TYPES[$pattern]['id'],
                            'resource_pattern' => $pattern,
                            'oasis_type' => null,
                        ];

                        $tileCounts[$pattern]++;
                    }

                    if (count($tileBatch) === $tileBatchSize) {
                        DB::table('map_tiles')->insert($tileBatch);
                        $tileBatch = [];
                    }

                    if ($oasisBatch !== [] && count($oasisBatch) === $tileBatchSize) {
                        DB::table('oases')->insert($oasisBatch);
                        $oasisBatch = [];
                    }
                }
            }

            if ($tileBatch !== []) {
                DB::table('map_tiles')->insert($tileBatch);
            }

            if ($oasisBatch !== []) {
                DB::table('oases')->insert($oasisBatch);
            }
        });

        $features = $world->features ?? [];

        $features['map'] = [
            'identifier' => $worldIdentifier,
            'bounds' => $bounds,
            'total_tiles' => $totalTiles,
            'tile_counts' => $tileCounts,
            'oasis_counts' => $oasisCounts,
        ];

        $world->forceFill([
            'features' => $features,
        ])->save();
    }

    /**
     * Calculate the oasis respawn timestamp adjusted for the current world speed.
     */
    private function calculateRespawnAt(Carbon $reference, float $worldSpeed, int $oasisTypeId): Carbon
    {
        $preset = self::OASIS_PRESETS[$oasisTypeId] ?? null;

        if ($preset === null) {
            return $reference->copy();
        }

        $speed = $worldSpeed > 0 ? $worldSpeed : 1.0;
        $minutes = (int) ceil($preset['respawn_minutes'] / $speed);

        return $reference->copy()->addMinutes($minutes);
    }
}
