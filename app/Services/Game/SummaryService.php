<?php

namespace App\Services\Game;

use Illuminate\Support\Facades\DB;

class SummaryService
{
    private const TRIBE_COLUMNS = [
        1 => 'roman_players_count',
        2 => 'teuton_players_count',
        3 => 'gaul_players_count',
        6 => 'egyptians_players_count',
        7 => 'huns_players_count',
    ];

    public function incrementTribePopulation(int $tribe): void
    {
        $column = self::TRIBE_COLUMNS[$tribe] ?? null;

        DB::table('summary')->update([
            'players_count' => DB::raw('players_count + 1'),
        ]);

        if ($column !== null) {
            DB::table('summary')->update([
                $column => DB::raw(sprintf('%s + 1', $column)),
            ]);
        }
    }
}
