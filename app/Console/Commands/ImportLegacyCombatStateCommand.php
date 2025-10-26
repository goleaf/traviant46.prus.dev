<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Game\CapturedUnit;
use App\Models\Game\MovementOrder;
use App\Models\Game\ReinforcementGarrison;
use App\Models\Game\Village;
use App\Models\MessageRecipient;
use App\Models\Report;
use App\Models\ReportRecipient;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Throwable;

class ImportLegacyCombatStateCommand extends Command
{
    protected $signature = 'travian:import-combat-state
        {--chunk=200 : Number of records to process per batch}
        {--dry-run : Simulate the import without persisting changes}';

    protected $description = 'Import legacy troop movements, reinforcements, captured troops, and combat reports.';

    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        $this->components->info(sprintf(
            'Starting combat state import (chunk size: %d, dry-run: %s)',
            $chunkSize,
            $dryRun ? 'yes' : 'no',
        ));

        try {
            $movementSummary = $this->importMovements($chunkSize, $dryRun);
            $garrisonSummary = $this->importGarrisons($chunkSize, $dryRun);
            $capturedSummary = $this->importCapturedUnits($chunkSize, $dryRun);
            $reportSummary = $this->importReports($chunkSize, $dryRun);
        } catch (Throwable $exception) {
            Log::error('legacy.import.combat.failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $this->components->error('Combat import halted: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Movement orders processed: %d (%d created, %d updated)',
            $movementSummary['processed'],
            $movementSummary['created'],
            $movementSummary['updated'],
        ));

        $this->components->info(sprintf(
            'Reinforcement garrisons processed: %d',
            $garrisonSummary['processed'],
        ));

        $this->components->info(sprintf(
            'Captured units processed: %d',
            $capturedSummary['processed'],
        ));

        $this->components->info(sprintf(
            'Combat reports processed: %d (%d created, %d updated)',
            $reportSummary['processed'],
            $reportSummary['created'],
            $reportSummary['updated'],
        ));

        $this->reconcileTroopMovements();

        return self::SUCCESS;
    }

    /**
     * @return array{processed: int, created: int, updated: int}
     */
    private function importMovements(int $chunkSize, bool $dryRun): array
    {
        $query = DB::connection('legacy')->table('movement')->orderBy('id');
        $processed = $created = $updated = 0;

        $query->lazy()->chunk($chunkSize)->each(function (LazyCollection $chunk) use (&$processed, &$created, &$updated, $dryRun): void {
            $chunk->each(function (object $row) use (&$processed, &$created, &$updated, $dryRun): void {
                $processed++;

                $originVillageId = $this->resolveVillageId($row->kid ?? null);
                $targetVillageId = $this->resolveVillageId($row->to_kid ?? null);

                if ($originVillageId === null || $targetVillageId === null) {
                    return;
                }

                $ownerId = $this->resolveUserId($row->uid ?? null);
                $departAt = $this->resolveTimestamp($row->start_time ?? null);
                $arriveAt = $this->resolveTimestamp($row->end_time ?? null);

                $status = $arriveAt !== null && $arriveAt->isPast() ? 'completed' : 'in_transit';

                $attributes = [
                    'user_id' => $ownerId,
                    'origin_village_id' => $originVillageId,
                    'target_village_id' => $targetVillageId,
                    'movement_type' => $this->mapMovementType($row),
                    'mission' => $this->mapMission($row),
                    'status' => $status,
                    'checksum' => $row->checksum ?? null,
                    'depart_at' => $departAt,
                    'arrive_at' => $arriveAt,
                    'return_at' => $this->resolveTimestamp($row->return_time ?? null),
                    'processed_at' => $status === 'completed' ? ($arriveAt ?? Carbon::now()) : null,
                    'payload' => [
                        'units' => $this->extractUnitPayload($row),
                        'resources' => $this->extractResourcePayload($row),
                    ],
                    'metadata' => [
                        'mode' => $row->mode ?? null,
                        'attack_type' => $row->attack_type ?? null,
                        'spy_type' => $row->spyType ?? null,
                        'ctar1' => $row->ctar1 ?? null,
                        'ctar2' => $row->ctar2 ?? null,
                        'redeploy_hero' => (bool) ($row->redeployHero ?? false),
                    ],
                ];

                if ($dryRun) {
                    return;
                }

                /** @var MovementOrder $movement */
                $movement = MovementOrder::query()->updateOrCreate(
                    ['legacy_movement_id' => (int) $row->id],
                    $attributes,
                );

                $movement->wasRecentlyCreated ? $created++ : $updated++;
            });
        });

        return compact('processed', 'created', 'updated');
    }

    /**
     * @return array{processed: int}
     */
    private function importGarrisons(int $chunkSize, bool $dryRun): array
    {
        $query = DB::connection('legacy')->table('enforcement')->orderBy('id');
        $processed = 0;

        $query->lazy()->chunk($chunkSize)->each(function (LazyCollection $chunk) use (&$processed, $dryRun): void {
            $chunk->each(function (object $row) use (&$processed, $dryRun): void {
                $processed++;

                $ownerId = $this->resolveUserId($row->uid ?? null);
                $homeVillageId = $this->resolveVillageId($row->kid ?? null);
                $stationedVillageId = $this->resolveVillageId($row->to_kid ?? null);

                if ($stationedVillageId === null) {
                    return;
                }

                if ($dryRun) {
                    return;
                }

                ReinforcementGarrison::query()->updateOrCreate(
                    ['legacy_enforcement_id' => (int) $row->id],
                    [
                        'owner_user_id' => $ownerId,
                        'home_village_id' => $homeVillageId,
                        'stationed_village_id' => $stationedVillageId,
                        'supporting_alliance_id' => $row->aid ?? null,
                        'unit_composition' => $this->extractUnitPayload($row),
                        'upkeep' => (int) ($row->pop ?? 0),
                        'is_active' => true,
                        'deployed_at' => Carbon::now(),
                        'last_synced_at' => Carbon::now(),
                        'metadata' => [
                            'race' => $row->race ?? null,
                        ],
                    ],
                );
            });
        });

        return compact('processed');
    }

    /**
     * @return array{processed: int}
     */
    private function importCapturedUnits(int $chunkSize, bool $dryRun): array
    {
        $query = DB::connection('legacy')->table('trapped')->orderBy('id');
        $processed = 0;

        $query->lazy()->chunk($chunkSize)->each(function (LazyCollection $chunk) use (&$processed, $dryRun): void {
            $chunk->each(function (object $row) use (&$processed, $dryRun): void {
                $processed++;

                $captorVillageId = $this->resolveVillageId($row->to_kid ?? null);

                if ($captorVillageId === null) {
                    return;
                }

                if ($dryRun) {
                    return;
                }

                CapturedUnit::query()->updateOrCreate(
                    ['legacy_trapped_id' => (int) $row->id],
                    [
                        'captor_village_id' => $captorVillageId,
                        'source_village_id' => $this->resolveVillageId($row->kid ?? null),
                        'owner_user_id' => $this->resolveUserId($row->uid ?? null),
                        'unit_composition' => $this->extractUnitPayload($row),
                        'status' => 'captured',
                        'captured_at' => Carbon::now(),
                        'metadata' => [
                            'race' => $row->race ?? null,
                        ],
                    ],
                );
            });
        });

        return compact('processed');
    }

    /**
     * @return array{processed: int, created: int, updated: int}
     */
    private function importReports(int $chunkSize, bool $dryRun): array
    {
        $query = DB::connection('legacy')->table('ndata')->orderBy('id');
        $processed = $created = $updated = 0;

        $query->lazy()->chunk($chunkSize)->each(function (LazyCollection $chunk) use (&$processed, &$created, &$updated, $dryRun): void {
            $chunk->each(function (object $row) use (&$processed, &$created, &$updated, $dryRun): void {
                $processed++;

                $ownerId = $this->resolveUserId($row->uid ?? null);
                $originVillageId = $this->resolveVillageId($row->kid ?? null);
                $targetVillageId = $this->resolveVillageId($row->to_kid ?? null);
                $triggeredAt = $this->resolveTimestamp($row->time ?? null);

                $attributes = [
                    'user_id' => $ownerId,
                    'alliance_id' => $row->aid ?? null,
                    'origin_village_id' => $originVillageId,
                    'target_village_id' => $targetVillageId,
                    'report_type' => $this->mapReportType($row->type ?? null),
                    'category' => $row->category ?? null,
                    'delivery_scope' => (int) ($row->is_public ?? 0) === 1 ? 'public' : 'personal',
                    'is_system_generated' => (bool) ($row->is_system ?? false),
                    'is_persistent' => (bool) ($row->is_persistent ?? false),
                    'loss_percentage' => $row->losses ?? null,
                    'payload' => $this->decodeJson($row->data ?? null),
                    'bounty' => $this->decodeJson($row->bounty ?? null),
                    'triggered_at' => $triggeredAt,
                    'viewed_at' => ((int) ($row->viewed ?? 0)) === 1 ? $triggeredAt : null,
                    'archived_at' => ((int) ($row->archive ?? 0)) === 1 ? Carbon::now() : null,
                    'deleted_at' => ((int) ($row->delete ?? 0)) === 1 ? Carbon::now() : null,
                    'metadata' => [
                        'share_key' => $row->share_key ?? null,
                        'legacy_id' => $row->id ?? null,
                    ],
                ];

                if ($dryRun) {
                    return;
                }

                /** @var Report $report */
                $report = Report::query()->updateOrCreate(
                    ['legacy_report_id' => (int) $row->id],
                    $attributes,
                );

                $report->wasRecentlyCreated ? $created++ : $updated++;

                if ($ownerId !== null) {
                    ReportRecipient::query()->updateOrCreate(
                        [
                            'report_id' => $report->getKey(),
                            'recipient_id' => $ownerId,
                        ],
                        [
                            'visibility_scope' => 'personal',
                            'status' => ((int) ($row->viewed ?? 0)) === 1 ? 'read' : 'unread',
                            'is_flagged' => (bool) ($row->delete ?? false),
                            'viewed_at' => ((int) ($row->viewed ?? 0)) === 1 ? $triggeredAt : null,
                            'archived_at' => ((int) ($row->archive ?? 0)) === 1 ? Carbon::now() : null,
                            'deleted_at' => ((int) ($row->delete ?? 0)) === 1 ? Carbon::now() : null,
                            'forwarded_at' => ((int) ($row->forwarded ?? 0)) === 1 ? Carbon::now() : null,
                            'share_token' => $row->share_key ?? null,
                            'metadata' => [
                                'loss_filter' => $row->losses ?? null,
                            ],
                        ],
                    );
                }
            });
        });

        return compact('processed', 'created', 'updated');
    }

    private function reconcileTroopMovements(): void
    {
        $overdueMovements = MovementOrder::query()
            ->where('status', 'in_transit')
            ->whereNotNull('arrive_at')
            ->where('arrive_at', '<', Carbon::now()->subMinutes(5))
            ->count();

        $deletedRecipients = MessageRecipient::query()
            ->where('status', 'deleted')
            ->whereNull('deleted_at')
            ->count();

        $this->components->twoColumnDetail(
            'Overdue movements awaiting resolution',
            (string) $overdueMovements,
        );

        if ($deletedRecipients > 0) {
            $this->components->warn(sprintf(
                '%d message recipients remain flagged as deleted without timestamp metadata.',
                $deletedRecipients,
            ));
        }

        $incompleteReports = Report::query()
            ->whereNull('triggered_at')
            ->count();

        if ($incompleteReports > 0) {
            $this->components->warn(sprintf(
                '%d reports missing timestamps were imported. Consider rerunning with legacy clock reconciliation.',
                $incompleteReports,
            ));
        } else {
            $this->components->info('Combat reports imported with complete timestamp metadata.');
        }
    }

    private function resolveVillageId(mixed $legacyKid): ?int
    {
        if ($legacyKid === null) {
            return null;
        }

        return Village::query()
            ->where('legacy_kid', $legacyKid)
            ->value('id');
    }

    private function resolveUserId(mixed $legacyUid): ?int
    {
        if ($legacyUid === null) {
            return null;
        }

        return User::query()
            ->where('legacy_uid', $legacyUid)
            ->value('id');
    }

    /**
     * @return array<string, int>
     */
    private function extractUnitPayload(object $row): array
    {
        $units = [];

        foreach (range(1, 11) as $slot) {
            $column = 'u'.$slot;
            if (property_exists($row, $column)) {
                $units['u'.$slot] = (int) $row->{$column};
            }
        }

        return $units;
    }

    /**
     * @return array<string, int>
     */
    private function extractResourcePayload(object $row): array
    {
        $resources = [];

        foreach (['wood', 'clay', 'iron', 'crop'] as $resource) {
            if (property_exists($row, $resource)) {
                $resources[$resource] = (int) $row->{$resource};
            }
        }

        return $resources;
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

    private function mapMovementType(object $row): string
    {
        $mode = (int) ($row->mode ?? 0);
        $attackType = (int) ($row->attack_type ?? 0);

        return match (true) {
            $mode === 0 && $attackType === 2 => 'raid',
            $mode === 0 && $attackType === 3 => 'scout',
            $mode === 1 => 'reinforcement',
            $mode === 2 => 'return',
            default => 'attack',
        };
    }

    private function mapMission(object $row): ?string
    {
        $mode = (int) ($row->mode ?? 0);
        $spyType = (int) ($row->spyType ?? 0);

        return match (true) {
            $mode === 0 && $spyType === 1 => 'resource-scout',
            $mode === 0 && $spyType === 2 => 'troop-scout',
            $mode === 3 => 'settle',
            default => null,
        };
    }

    private function mapReportType(mixed $legacyType): string
    {
        $type = (int) ($legacyType ?? 0);

        return match ($type) {
            1 => 'attack',
            2 => 'raid',
            3 => 'reinforcement',
            4 => 'trade',
            5 => 'scouting',
            6 => 'wonder',
            7 => 'system',
            default => 'misc',
        };
    }

    private function decodeJson(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : ['legacy' => $value];
    }
}
