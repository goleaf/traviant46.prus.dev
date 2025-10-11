<?php

namespace App\Jobs;

use App\Models\Game\Village;
use App\Models\Game\VillageResource;
use App\Models\Game\VillageUnit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessStarvation implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function __construct(private readonly int $chunkSize = 50)
    {
        $this->onQueue('automation');
    }

    public function handle(): void
    {
        if (!Config::get('game.starvation.enabled', true)) {
            return;
        }

        Village::query()
            ->whereHas('resources', function ($query): void {
                $query
                    ->where('resource_type', VillageResource::TYPE_CROP)
                    ->where(function ($inner): void {
                        $inner
                            ->where('current_stock', '<=', 0)
                            ->orWhere('production_per_hour', '<=', 0);
                    });
            })
            ->with(['resources' => function ($query): void {
                $query->where('resource_type', VillageResource::TYPE_CROP);
            }, 'units'])
            ->chunkById($this->chunkSize, function ($villages): void {
                foreach ($villages as $village) {
                    $this->handleVillage($village);
                }
            });
    }

    private function handleVillage(Village $village): void
    {
        try {
            DB::transaction(function () use ($village): void {
                $lockedVillage = Village::query()
                    ->whereKey($village->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($lockedVillage === null) {
                    return;
                }

                $cropResource = VillageResource::query()
                    ->where('village_id', $lockedVillage->getKey())
                    ->where('resource_type', VillageResource::TYPE_CROP)
                    ->lockForUpdate()
                    ->first();

                if ($cropResource === null) {
                    return;
                }

                $units = VillageUnit::query()
                    ->where('village_id', $lockedVillage->getKey())
                    ->lockForUpdate()
                    ->get();

                $troopUpkeep = $this->calculateTroopUpkeep($units);
                $netCropPerHour = (float) $cropResource->production_per_hour - $lockedVillage->population - $troopUpkeep;
                $currentStock = (float) $cropResource->current_stock;

                if ($currentStock > 0 && $netCropPerHour >= 0) {
                    return;
                }

                if ($netCropPerHour >= 0) {
                    if ($currentStock < 0) {
                        $cropResource->current_stock = 0;
                        $cropResource->save();
                    }

                    return;
                }

                if ($currentStock > 0) {
                    return;
                }

                $shortage = abs($netCropPerHour);
                $killedUnits = [];

                $units
                    ->sortByDesc(fn (VillageUnit $unit) => $this->cropConsumptionForType($unit->unit_type_id))
                    ->each(function (VillageUnit $unit) use (&$shortage, &$killedUnits): void {
                        if ($shortage <= 0) {
                            return;
                        }

                        $consumption = $this->cropConsumptionForType($unit->unit_type_id);
                        $quantity = (int) $unit->quantity;

                        if ($consumption <= 0 || $quantity <= 0) {
                            return;
                        }

                        $unitsToKill = min($quantity, (int) ceil($shortage / $consumption));
                        if ($unitsToKill <= 0) {
                            return;
                        }

                        $unit->quantity = max(0, $quantity - $unitsToKill);
                        $unit->save();

                        $shortage -= $unitsToKill * $consumption;
                        $killedUnits[] = [
                            'unit_type_id' => $unit->unit_type_id,
                            'killed' => $unitsToKill,
                            'consumption_per_unit' => $consumption,
                        ];
                    });

                $cropResource->current_stock = min((float) $cropResource->storage_capacity, 0.0);
                $cropResource->save();

                Log::warning('Village experienced crop starvation.', [
                    'village_id' => $lockedVillage->getKey(),
                    'shortage_per_hour' => round(abs($netCropPerHour), 4),
                    'killed_units' => $killedUnits,
                    'remaining_shortage' => max(0, round($shortage, 4)),
                ]);
            }, 5);
        } catch (Throwable $exception) {
            Log::error('Failed to process village starvation.', [
                'village_id' => $village->getKey(),
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    private function calculateTroopUpkeep($units): float
    {
        return (float) $units->sum(function (VillageUnit $unit): float {
            $consumption = $this->cropConsumptionForType($unit->unit_type_id);

            return $consumption * (int) $unit->quantity;
        });
    }

    private function cropConsumptionForType(int $unitTypeId): int
    {
        return (int) Config::get("game.units.crop_consumption.$unitTypeId", 1);
    }
}
