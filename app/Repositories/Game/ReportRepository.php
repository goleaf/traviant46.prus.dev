<?php

declare(strict_types=1);

namespace App\Repositories\Game;

use App\Models\Report;
use App\Models\ReportRecipient;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class ReportRepository
{
    /**
     * @param array<string, mixed> $attackerPayload
     * @param array<string, mixed> $defenderPayload
     * @return array{attacker?: Report, defender?: Report}
     */
    public function createCombatReports(
        ?int $attackerUserId,
        ?int $defenderUserId,
        ?int $originVillageId,
        ?int $targetVillageId,
        array $attackerPayload,
        array $defenderPayload,
        ?CarbonInterface $triggeredAt = null
    ): array {
        $triggered = $triggeredAt ?? Carbon::now();

        $reports = [];

        if ($attackerUserId !== null) {
            $reports['attacker'] = $this->createReport(
                userId: $attackerUserId,
                category: 'attack',
                originVillageId: $originVillageId,
                targetVillageId: $targetVillageId,
                payload: $attackerPayload,
                triggeredAt: $triggered,
            );
        }

        if ($defenderUserId !== null) {
            $reports['defender'] = $this->createReport(
                userId: $defenderUserId,
                category: 'defense',
                originVillageId: $originVillageId,
                targetVillageId: $targetVillageId,
                payload: $defenderPayload,
                triggeredAt: $triggered,
            );
        }

        return $reports;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createReport(
        int $userId,
        string $category,
        ?int $originVillageId,
        ?int $targetVillageId,
        array $payload,
        CarbonInterface $triggeredAt
    ): Report {
        $report = Report::query()->create([
            'user_id' => $userId,
            'origin_village_id' => $originVillageId,
            'target_village_id' => $targetVillageId,
            'report_type' => 'combat',
            'category' => $category,
            'delivery_scope' => 'individual',
            'is_system_generated' => true,
            'is_persistent' => false,
            'payload' => $payload,
            'triggered_at' => $triggeredAt,
        ]);

        ReportRecipient::query()->create([
            'report_id' => $report->getKey(),
            'recipient_id' => $userId,
            'visibility_scope' => 'owner',
            'status' => 'unread',
            'is_flagged' => false,
        ]);

        return $report;
    }
}
