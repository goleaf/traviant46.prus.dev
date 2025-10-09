<?php

namespace Core\Jobs;

class JobScheduler
{
    private static $instance;

    /** @var Job[] */
    private $jobs = [];

    private $running = false;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function register(Job $job)
    {
        $this->jobs[$job->getName()] = $job;
    }

    public function run()
    {
        $this->running = true;
        while ($this->running) {
            $sleepInterval = $this->jobs ? $this->getSleepInterval() : 1;
            foreach ($this->jobs as $job) {
                $job->runAction();
            }
            usleep((int)max($sleepInterval * 1000000, 500));
        }
    }

    public function stop()
    {
        $this->running = false;
    }

    private function getSleepInterval()
    {
        $minInterval = null;
        foreach ($this->jobs as $job) {
            $interval = $job->getIntervalSeconds();
            if ($interval <= 0) {
                continue;
            }
            if ($minInterval === null || $interval < $minInterval) {
                $minInterval = $interval;
            }
        }

        return $minInterval === null ? 0.5 : max(0.0005, $minInterval);
    }
}

