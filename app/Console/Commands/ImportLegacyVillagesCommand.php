<?php

declare(strict_types=1);

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Game\Village;
use App\Models\Game\VillageResource;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Throwable;

class ImportLegacyVillagesCommand extends Command
{
    protected $signature = 'travian:import-villages
        {--chunk=250 : Number of legacy rows to process per batch}
        {--dry-run : Simulate the import without writing to the database}';

    protected $description = 'Import legacy Travian village data with resource snapshots and loyalty metadata.';

    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        $this->components->info(sprintf(
            'Beginning legacy village import (chunk size: %d, dry-run: %s)',
            $chunkSize,
            $dryRun ? 'yes' : 'no',
        ));

        $legacyQuery = DB::connection('legacy')
            ->table('vdata')
            ->orderBy('kid');

        $processed = 0;
        $created = 0;
        $updated = 0;

        try {
            $legacyQuery->lazy()->chunk($chunkSize)->each(function (LazyCollection $chunk) use ($dryRun, &$processed, &$created, &$updated): void {
                $chunk->each(function (object $row) use ($dryRun, &$processed, &$created, &$updated): void {
                    $processed++;

                    $ownerId = null;
                    if (property_exists($row, 'owner')) {
                        $ownerId = User::query()
                            ->where('legacy_uid', $row->owner)
                            ->value('id');
                    }

                    $watcherId = null;
                    if (property_exists($row, 'checker') && $row->checker !== null) {
                        $watcherId = User::query()
                            ->where('legacy_uid', $row->checker)
                            ->value('id');
                    }

                    $attributes = [
                        'legacy_kid' => property_exists($row, 'kid') ? (int) $row->kid : null,
                        'user_id' => $ownerId,
                        'watcher_user_id' => $watcherId,
                        'name' => (string) ($row->name ?? 'Unnamed Village'),
                        'x_coordinate' => (int) ($row->x ?? 0),
                        'y_coordinate' => (int) ($row->y ?? 0),
                        'terrain_type' => (int) ($row->fieldtype ?? 1),
                        'village_category' => $this->resolveVillageCategory($row),
                        'is_capital' => (bool) ($row->capital ?? false),
                        'is_wonder_village' => (bool) ($row->wonder ?? false),
                        'population' => (int) ($row->pop ?? ($row->population ?? 0)),
                        'loyalty' => min(100, max(0, (int) ($row->loyalty ?? 100))),
                        'culture_points' => (int) ($row->cp ?? 0),
                        'resource_balances' => $this->extractResourceBalances($row),
                        'storage' => $this->extractStorageCapacities($row),
                        'production' => $this->extractProductionRates($row),
                        'defense_bonus' => $this->extractDefenseBonuses($row),
                        'founded_at' => $this->resolveTimestamp($row->creation ?? null),
                        'abandoned_at' => $this->resolveTimestamp($row->abandoned_at ?? null),
                        'last_loyalty_change_at' => $this->resolveTimestamp($row->last_loyalty_change ?? null),
                    ];

                    if ($dryRun) {
                        return;
                    }

                    $village = Village::query()->where('legacy_kid', $attributes['legacy_kid'])->first();

                    if ($village === null) {
                        $village = Village::query()->create($attributes);
                        $created++;
                    } else {
                        $village->fill($attributes);
                        $village->save();
                        $updated++;
                    }

                    $this->syncResourceSnapshots($village, $row);
                });

                $this->components->twoColumnDetail(
                    sprintf('Processed %d legacy villages', $processed),
                    sprintf('created %d · updated %d', $created, $updated),
                );
            });
        } catch (Throwable $exception) {
            Log::error('legacy.import.villages.failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $this->components->error('Village import halted: '.$exception->getMessage());

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->components->info(sprintf('Dry-run complete. %d villages analysed.', $processed));
        } else {
            $this->components->success(sprintf(
                'Village import complete (%d processed, %d created, %d updated).',
                $processed,
                $created,
                $updated,
            ));
        }

        return self::SUCCESS;
    }

    /**
     * @return array{wood: int, clay: int, iron: int, crop: int}
     */
    private function extractResourceBalances(object $row): array
    {
        return [
            'wood' => (int) ($row->wood ?? 0),
            'clay' => (int) ($row->clay ?? 0),
            'iron' => (int) ($row->iron ?? 0),
            'crop' => (int) ($row->crop ?? 0),
        ];
    }

    /**
     * @return array{warehouse: int, granary: int, extra: array<string, int>}
     */
    private function extractStorageCapacities(object $row): array
    {
        $warehouse = (int) ($row->maxstore ?? 0);
        $granary = (int) ($row->maxcrop ?? 0);
        $extraWarehouse = (int) ($row->extraMaxstore ?? 0);
        $extraGranary = (int) ($row->extraMaxcrop ?? 0);

        return [
            'warehouse' => $warehouse + $extraWarehouse,
            'granary' => $granary + $extraGranary,
            'extra' => [
                'warehouse' => $extraWarehouse,
                'granary' => $extraGranary,
            ],
        ];
    }

    /**
     * @return array{wood: int, clay: int, iron: int, crop: int}
     */
    private function extractProductionRates(object $row): array
    {
        return [
            'wood' => (int) ($row->woodp ?? 0),
            'clay' => (int) ($row->clayp ?? 0),
            'iron' => (int) ($row->ironp ?? 0),
            'crop' => (int) ($row->cropp ?? 0),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function extractDefenseBonuses(object $row): array
    {
        $bonuses = [
            'morale' => (int) ($row->loyalty ?? 100),
        ];

        if (property_exists($row, 'upkeep')) {
            $bonuses['upkeep'] = (int) $row->upkeep;
        }

        if (property_exists($row, 'hero_bonus')) {
            $bonuses['hero'] = (int) $row->hero_bonus;
        }

        return $bonuses;
    }

    private function resolveVillageCategory(object $row): ?string
    {
        $type = property_exists($row, 'type') ? (int) $row->type : null;

        return match ($type) {
            1 => 'capital',
            2 => 'natar',
            3 => 'wonder',
            4 => 'oasis',
            default => null,
        };
    }

    private function resolveTimestamp(mixed $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value);
        }

        if (is_string($value)) {
            try {
                return Carbon::parse($value);
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    private function syncResourceSnapshots(Village $village, object $row): void
    {
        foreach ([
            'wood' => ['production' => $row->woodp ?? 0],
            'clay' => ['production' => $row->clayp ?? 0],
            'iron' => ['production' => $row->ironp ?? 0],
            'crop' => ['production' => $row->cropp ?? 0],
        ] as $resourceType => $data) {
            VillageResource::query()->updateOrCreate(
                [
                    'village_id' => $village->getKey(),
                    'resource_type' => $resourceType,
                ],
                [
                    'level' => 0,
                    'production_per_hour' => (int) $data['production'],
                    'storage_capacity' => $resourceType === 'crop'
                        ? (int) (($row->maxcrop ?? 0) + ($row->extraMaxcrop ?? 0))
                        : (int) (($row->maxstore ?? 0) + ($row->extraMaxstore ?? 0)),
                    'bonuses' => [
                        'oasis' => (int) ($row->oasis_bonus ?? 0),
                        'artifact' => (int) ($row->artifact_bonus ?? 0),
                    ],
                    'last_collected_at' => $this->resolveTimestamp($row->last_update ?? null),
                ],
            );
        }
    }
}
