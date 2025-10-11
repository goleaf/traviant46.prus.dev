<?php

namespace App\Jobs;

use Model\NatarsModel;

class ProcessNatarDefense
{
    private NatarsModel $natarsModel;

    public function __construct(?NatarsModel $natarsModel = null)
    {
        $this->natarsModel = $natarsModel ?? new NatarsModel();
    }

    public function __invoke(): void
    {
        $this->natarsModel->handleNatarDefense();
    }

    public function runAction(): void
    {
        $this();
    }
}
