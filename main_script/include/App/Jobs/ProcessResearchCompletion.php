<?php

namespace App\Jobs;

use Core\Automation;

class ProcessResearchCompletion
{
    /** @var Automation */
    private $automation;

    /**
     * @param Automation|null $automation
     */
    public function __construct(Automation $automation = null)
    {
        $this->automation = $automation ?: Automation::getInstance();
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $this->automation->researchComplete();
    }

    /**
     * Allow the job to be invoked like a callable.
     */
    public function __invoke()
    {
        $this->handle();
    }
}
