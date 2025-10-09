<?php

namespace App\Jobs;

use Core\Database\DB;
use Model\TrainingModel;
use function getGame;
use function getGameSpeed;
use function miliseconds;
use function nanoseconds;

class ProcessTroopTraining
{
    private const BATCH_LIMIT = 100;
    private const IMMEDIATE_UNIT_SLOTS = [9, 10, 11];

    public function runAction(): void
    {
        $db = DB::getInstance();
        $trainingModel = new TrainingModel();

        $this->processImmediateTrainings($db, $trainingModel);
        $this->processQueuedTrainings($db, $trainingModel);
    }

    private function processImmediateTrainings(DB $db, TrainingModel $trainingModel): void
    {
        $this->processBatch(
            $db,
            $trainingModel,
            sprintf('nr IN(%s)', $this->getImmediateSlotsList()),
            $this->getCurrentTime()
        );
    }

    private function processQueuedTrainings(DB $db, TrainingModel $trainingModel): void
    {
        $delay = $this->calculateDelayedProcessingOffset();
        $this->processBatch(
            $db,
            $trainingModel,
            sprintf('nr NOT IN(%s)', $this->getImmediateSlotsList()),
            $this->getCurrentTime($delay)
        );
    }

    private function processBatch(DB $db, TrainingModel $trainingModel, string $slotFilter, $currentTime): void
    {
        $query = sprintf(
            'SELECT * FROM training WHERE %s AND commence < %s LIMIT %d',
            $slotFilter,
            $currentTime,
            self::BATCH_LIMIT
        );
        $result = $db->query($query);
        if (!($result instanceof \mysqli_result)) {
            return;
        }

        while ($row = $result->fetch_assoc()) {
            $trainingModel->handleTrainingCompleteResult($row);
        }
        $result->free();
    }

    private function getCurrentTime(int $delaySeconds = 0)
    {
        if (getGame('useNanoseconds')) {
            return nanoseconds() - ($delaySeconds * 1000000000);
        }
        if (getGame('useMilSeconds')) {
            return miliseconds() - ($delaySeconds * 1000);
        }
        return time() - $delaySeconds;
    }

    private function calculateDelayedProcessingOffset(): int
    {
        $speed = (int)getGameSpeed();
        if ($speed <= 20) {
            return 0;
        }
        $delay = max(0, (int)floor($speed / 1000) * 5);
        return min($delay, 30);
    }

    private function getImmediateSlotsList(): string
    {
        return implode(',', self::IMMEDIATE_UNIT_SLOTS);
    }
}
