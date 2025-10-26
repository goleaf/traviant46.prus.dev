<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SitterDelegation;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DelegationAssigned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly SitterDelegation $delegation,
        public readonly User $actor,
    ) {
        $this->delegation->loadMissing('owner', 'sitter');
    }
}
