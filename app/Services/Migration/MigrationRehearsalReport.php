<?php

namespace App\Services\Migration;

class MigrationRehearsalReport
{
    public string $migrationPreview = '';
    public string $regressionSummary = '';

    /**
     * @var list<array{name: string, context: array<string, mixed>}> 
     */
    public array $events = [];

    public function __construct(
        public readonly string $feature,
        public readonly string $primaryConnection,
        public readonly string $legacyConnection,
    ) {}

    public function addEvent(string $name, array $context = []): void
    {
        $this->events[] = [
            'name' => $name,
            'context' => $context,
        ];
    }
}
