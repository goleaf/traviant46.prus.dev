<?php

namespace App\Jobs;

use Core\Database\DB;
use Model\TrainingModel;
use function getGame;
use function getGameSpeed;
use function implode;
use function miliseconds;
use function nanoseconds;
use function time;

class ProcessTroopTraining
{
    private const IMMEDIATE_UNIT_NUMBERS = [9, 10, 11];

    private TrainingModel $trainingModel;

    private DB $db;

    private int $chunkSize;

    public function __construct(?TrainingModel $trainingModel = null, ?DB $db = null, int $chunkSize = 100)
    {
        $this->trainingModel = $trainingModel ?? new TrainingModel();
        $this->db = $db ?? DB::getInstance();
        $this->chunkSize = max(1, $chunkSize);
    }

    public function handle(): int
    {
        $processed = 0;
        $processed += $this->processImmediateTrainings();
        $processed += $this->processRegularTrainings();

        return $processed;
    }

    private function processImmediateTrainings(): int
    {
        return $this->processQuery(
            sprintf('nr IN(%s)', implode(',', self::IMMEDIATE_UNIT_NUMBERS)),
            0
        );
    }

    private function processRegularTrainings(): int
    {
        $delay = 0;
        $gameSpeed = getGameSpeed();
        if ($gameSpeed > 20) {
            $delay = min(max(0, (int)floor($gameSpeed / 1000) * 5), 30);
        }

        return $this->processQuery(
            sprintf('nr NOT IN(%s)', implode(',', self::IMMEDIATE_UNIT_NUMBERS)),
            $delay
        );
    }

    private function processQuery(string $clause, int $delaySeconds): int
    {
        $cutoff = $this->resolveCutoffTime($delaySeconds);
        $result = $this->db->query(
            sprintf(
                'SELECT * FROM training WHERE %s AND commence < %d LIMIT %d',
                $clause,
                $cutoff,
                $this->chunkSize
            )
        );

        if ($result === false) {
            return 0;
        }

        $processed = 0;
        while ($row = $result->fetch_assoc()) {
            $this->trainingModel->handleTrainingCompleteResult($row);
            ++$processed;
        }

        return $processed;
    }

    private function resolveCutoffTime(int $delaySeconds): int
    {
        if (getGame('useNanoseconds')) {
            return (int)(nanoseconds() - ($delaySeconds * 1e9));
        }

        if (getGame('useMilSeconds')) {
            return (int)(miliseconds() - ($delaySeconds * 1000));
        }

        return time() - $delaySeconds;
    }
}
