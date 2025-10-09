<?php

namespace App\Jobs;

use Model\AdventureModel;

class ProcessAdventures
{
    private AdventureModel $adventureModel;

    public function __construct(?AdventureModel $adventureModel = null)
    {
        $this->adventureModel = $adventureModel ?? new AdventureModel();
    }

    public function handle(): void
    {
        $this->adventureModel->checkForNewAdventures();
    }
}
