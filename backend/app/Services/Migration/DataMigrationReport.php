<?php

namespace App\Services\Migration;

class DataMigrationReport
{
    public string $feature;

    public string $sourceConnection;

    public string $targetConnection;

    public bool $truncateBeforeImport;

    /**
     * @var array<int, array{name: string, context: array<string, mixed>}>
     */
    public array $events = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    public array $tables = [];

    public function __construct(string $feature, string $sourceConnection, string $targetConnection, bool $truncateBeforeImport)
    {
        $this->feature = $feature;
        $this->sourceConnection = $sourceConnection;
        $this->targetConnection = $targetConnection;
        $this->truncateBeforeImport = $truncateBeforeImport;
    }

    public function addEvent(string $name, array $context = []): void
    {
        $this->events[] = [
            'name' => $name,
            'context' => $context,
        ];
    }

    public function addTableResult(string $table, array $result): void
    {
        $this->tables[$table] = $result;
    }
}
