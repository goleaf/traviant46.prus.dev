<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class QueuedVerifyEmail extends BaseVerifyEmail implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $queueConfiguration = config('mail.queue', []);

        $connection = $queueConfiguration['connection'] ?? null;
        $queue = $queueConfiguration['name'] ?? null;
        $retryAfter = $queueConfiguration['retry_after'] ?? null;

        if ($connection) {
            $this->onConnection($connection);
        }

        if ($queue) {
            $this->onQueue($queue);
        }

        if ($retryAfter) {
            $this->retryAfter((int) $retryAfter);
        }
    }
}

