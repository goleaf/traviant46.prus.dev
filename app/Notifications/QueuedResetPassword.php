<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class QueuedResetPassword extends BaseResetPassword implements ShouldQueue
{
    use Queueable;

    public function __construct(string $token)
    {
        parent::__construct($token);

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

