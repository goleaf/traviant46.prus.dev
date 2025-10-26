<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SitterDelegation;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DelegationRevoked
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly SitterDelegation $delegation,
        public readonly ?User $actor,
        public readonly string $reason,
    ) {
        $this->delegation->loadMissing('owner', 'sitter');
    }
}
