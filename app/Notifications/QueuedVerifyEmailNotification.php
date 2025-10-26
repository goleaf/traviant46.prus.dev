<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class QueuedVerifyEmailNotification extends BaseVerifyEmail implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue(config('queue.mail_queue', 'mail'));
    }

    /**
     * Define queue aliases per channel so notifications land on the mail queue.
     *
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        $queue = config('queue.mail_queue', 'mail');

        return [
            'mail' => $queue,
        ];
    }
}
