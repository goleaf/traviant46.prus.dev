<?php

namespace App\Jobs;

use Model\NatarsModel;

class ProcessArtifacts
{
    public function runAction()
    {
        (new NatarsModel())->runJobs();
    }
}
