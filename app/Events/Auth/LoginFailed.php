<?php

declare(strict_types=1);

namespace App\Events\Auth;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoginFailed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly ?User $user,
        public readonly string $identifier,
        public readonly string $guard,
        public readonly string $ipAddress,
        public readonly ?string $userAgent,
        public readonly int $attempts,
        public readonly int $maxAttempts,
        public readonly int $secondsUntilNextAttempt,
    ) {}
}
