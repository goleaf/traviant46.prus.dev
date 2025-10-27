<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TroopTypeSeeder extends Seeder
{
    /**
     * The relative path to the shared troop type definition JSON file.
     */
    private const DATA_FILE = 'data/troop_types.json';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timestamp = Carbon::now();

        // Build the upsert payload by reading the stat blocks from disk once per execution.
        $rows = [];

        foreach ($this->loadTroopDefinitions() as $tribe => $units) {
            foreach ($units as $unit) {
                $baseStats = $unit['base_stats'];

                if (! is_array($baseStats)) {
                    throw new RuntimeException('Invalid troop base stats payload encountered while seeding.');
                }

                if (! is_array($unit['training_costs'])) {
                    throw new RuntimeException('Invalid troop training cost payload encountered while seeding.');
                }

                $rows[] = [
                    'tribe' => (int) $tribe,
                    'code' => $unit['code'],
                    'name' => $unit['name'],
                    'attack' => $baseStats['attack'],
                    'def_inf' => $baseStats['def_infantry'],
                    'def_cav' => $baseStats['def_cavalry'],
                    'speed' => $unit['speed'],
                    'carry' => $unit['carry'],
                    'train_cost' => json_encode($unit['training_costs'], JSON_THROW_ON_ERROR),
                    'upkeep' => $unit['upkeep'],
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        // The composite key keeps tribe specific overrides from duplicating rows across runs.
        DB::table('troop_types')->upsert(
            $rows,
            ['tribe', 'code'],
            ['name', 'attack', 'def_inf', 'def_cav', 'speed', 'carry', 'train_cost', 'upkeep', 'updated_at'],
        );
    }

    /**
     * Load and decode the troop definitions that are shared with the frontend calculators.
     *
     * @return array<int|string, array<int, array<string, mixed>>> Parsed definitions keyed by tribe id.
     */
    private function loadTroopDefinitions(): array
    {
        $path = database_path(self::DATA_FILE);

        if (! is_file($path)) {
            throw new RuntimeException(sprintf('Troop type definition file not found at %s', $path));
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Failed to read troop type definitions from %s', $path));
        }

        /** @var array<int|string, array<int, array<string, mixed>>> $decoded */
        $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
