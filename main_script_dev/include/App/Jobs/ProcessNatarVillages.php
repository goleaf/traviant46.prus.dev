<?php

namespace App\Jobs;

use Model\NatarsModel;

class ProcessNatarVillages
{
    private NatarsModel $natarsModel;

    public function __construct(?NatarsModel $natarsModel = null)
    {
        $this->natarsModel = $natarsModel ?? new NatarsModel();
    }

    public function __invoke(): void
    {
        $this->natarsModel->handleNatarVillages();
        $this->natarsModel->handleNatarExpansion();
    }

    public function runAction(): void
    {
        $this();
    }
}
