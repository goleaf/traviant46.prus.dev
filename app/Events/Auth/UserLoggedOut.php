<?php

namespace App\Events\Auth;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLoggedOut
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly ?User $user,
        public readonly string $guard,
        public readonly string $ipAddress,
        public readonly ?string $userAgent,
    ) {}
}
