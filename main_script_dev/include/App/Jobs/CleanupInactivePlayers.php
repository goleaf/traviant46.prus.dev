<?php

namespace App\Jobs;

use Model\inactiveModel;

class CleanupInactivePlayers
{
    private inactiveModel $inactiveModel;

    public function __construct(?inactiveModel $inactiveModel = null)
    {
        $this->inactiveModel = $inactiveModel ?? new inactiveModel();
    }

    public function handle(): void
    {
        $this->inactiveModel->startWorker();
    }
}
