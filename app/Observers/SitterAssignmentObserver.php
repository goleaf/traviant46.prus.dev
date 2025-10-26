<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\SitterAssignment;
use App\Monitoring\Metrics\MetricRecorder;

class SitterAssignmentObserver
{
    public function __construct(private readonly MetricRecorder $metrics) {}

    public function created(SitterAssignment $assignment): void
    {
        $this->record('created', $assignment);
    }

    public function updated(SitterAssignment $assignment): void
    {
        $this->record('updated', $assignment);
    }

    public function deleted(SitterAssignment $assignment): void
    {
        $this->record('deleted', $assignment);
    }

    private function record(string $operation, SitterAssignment $assignment): void
    {
        $this->metrics->increment('sitter.assignment', 1.0, [
            'operation' => $operation,
            'has_expiry' => $assignment->expires_at !== null ? 'yes' : 'no',
        ]);
    }
}
