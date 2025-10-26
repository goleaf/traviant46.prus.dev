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
