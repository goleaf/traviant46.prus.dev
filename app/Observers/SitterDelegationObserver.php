<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\SitterDelegation;
use App\Monitoring\Metrics\MetricRecorder;
use App\ValueObjects\SitterPermissionSet;

class SitterDelegationObserver
{
    public function __construct(private readonly MetricRecorder $metrics) {}

    public function created(SitterDelegation $delegation): void
    {
        $this->record('created', $delegation);
    }

    public function updated(SitterDelegation $delegation): void
    {
        $this->record('updated', $delegation);
    }

    public function deleted(SitterDelegation $delegation): void
    {
        $this->record('deleted', $delegation);
    }

    private function record(string $operation, SitterDelegation $delegation): void
    {
        $fullMask = SitterPermissionSet::full()->toBitmask();

        $this->metrics->increment('sitter.delegation', 1.0, [
            'operation' => $operation,
            'profile' => $delegation->permissionBitmask() === $fullMask ? 'full' : 'custom',
            'has_expiry' => $delegation->expires_at !== null ? 'yes' : 'no',
        ]);
    }
}
