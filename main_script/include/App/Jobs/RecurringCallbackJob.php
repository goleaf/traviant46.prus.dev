<?php

namespace App\Jobs;

use Core\Automation;
use Core\Config;
use Core\Database\DB;
use Core\ErrorHandler;
use Core\Jobs\Job;
use Core\Queue\QueueManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RecurringCallbackJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private Job $job;

    public function __construct(Job $job)
    {
        $this->job = $job;
        $this->onQueue(getenv('AUTOMATION_QUEUE_NAME') ?: 'automation');
    }

    public function handle(): void
    {
        $config = Config::getInstance();
        $db = DB::getInstance();
        if (!$db->checkConnection()) {
            QueueManager::scheduleRecurring($this->job);
            return;
        }

        $result = $db->query('SELECT * FROM config LIMIT 1');
        if ($result && $result->num_rows) {
            $config->dynamic = (object)$result->fetch_assoc();
        }

        $dynamic = $config->dynamic ?? (object)[];

        $exclude = $this->job->getName() === 'postService' && (($dynamic->postServiceDone ?? 1) == 0);

        if (($dynamic->needsRestart ?? 0) == 1) {
            QueueManager::releaseRecurring($this->job->getName());
            return;
        }

        if (($dynamic->finishStatusSet ?? 0) == 1 && !$exclude) {
            QueueManager::releaseRecurring($this->job->getName());
            return;
        }

        if (($dynamic->automationState ?? 0) == 1 || $exclude) {
            $this->runCallback();
        }

        QueueManager::scheduleRecurring($this->job);
    }

    private function runCallback(): void
    {
        $callback = $this->job->getCallback();
        try {
            if (($callback['type'] ?? null) === 'automation') {
                $instance = Automation::getInstance();
                $method = $callback['method'];
                $instance->$method();
            } elseif (($callback['type'] ?? null) === 'model') {
                $class = $callback['class'];
                $method = $callback['method'];
                $instance = new $class();
                $instance->$method();
            } else {
                ErrorHandler::getInstance()->handleExceptions(
                    new \RuntimeException(sprintf('Unknown callback type for job "%s"', $this->job->getName()))
                );
            }
        } catch (Throwable $throwable) {
            ErrorHandler::getInstance()->handleExceptions($throwable);
        }
    }
}
