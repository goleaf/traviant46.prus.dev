<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class QueuedPasswordResetNotification extends BaseResetPassword implements ShouldQueue
{
    use Queueable;

    public function __construct(#[\SensitiveParameter] string $token)
    {
        parent::__construct($token);

        $this->configureQueue();
    }

    /**
     * Define queue aliases per channel so notifications land on the mail queue.
     *
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        $queueConfiguration = config('mail.queue', []);
        $queue = $queueConfiguration['name'] ?? config('queue.mail_queue', 'mail');

        return [
            'mail' => $queue,
        ];
    }

    private function configureQueue(): void
    {
        $queueConfiguration = config('mail.queue', []);

        $connection = $queueConfiguration['connection'] ?? config('queue.mail_connection');
        $queue = $queueConfiguration['name'] ?? config('queue.mail_queue', 'mail');
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
