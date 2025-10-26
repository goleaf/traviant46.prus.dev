<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Monitoring\Metrics\MetricRecorder;
use Illuminate\Auth\Events\Failed;

class RecordFailedLogin
{
    public function __construct(private readonly MetricRecorder $metrics) {}

    public function handle(Failed $event): void
    {
        $identifierField = config('fortify.username', 'email');
        $identifier = $event->credentials[$identifierField] ?? ($event->credentials['email'] ?? null);

        $this->metrics->increment('auth.logins', 1.0, [
            'status' => 'failed',
            'guard' => (string) $event->guard,
            'identified' => $identifier ? 'yes' : 'no',
            'failure' => $event->user !== null ? 'invalid_credentials' : 'user_not_found',
        ]);
    }
}
