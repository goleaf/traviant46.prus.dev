<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;

trait UsesConfiguredDatabase
{
    use RefreshDatabase {
        migrateFreshUsing as protected baseMigrateFreshUsing;
    }

    /**
     * @return array<string, mixed>
     */
    protected function migrateFreshUsing(): array
    {
        $paths = collect(glob(database_path('migrations/*.php')))
            ->reject(static fn (string $path) => str_contains($path, '2025_10_26_223544_create_messages_table.php'))
            ->map(static fn (string $path) => realpath($path) ?: $path)
            ->values()
            ->all();

        return array_merge($this->baseMigrateFreshUsing(), [
            '--path' => $paths,
            '--realpath' => true,
        ]);
    }
}
