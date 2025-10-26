<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Game\World;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class WorldSeeder extends Seeder
{
    public function run(): void
    {
        World::query()->updateOrCreate(
            ['name' => 'World #1'],
            [
                'speed' => 1.0,
                'features' => [],
                'starts_at' => Carbon::now()->subDay(),
                'status' => 'active',
            ],
        );
    }
}
