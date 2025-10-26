<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SitterDelegation;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DelegationUpdated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param list<string> $changedAttributes
     */
    public function __construct(
        public readonly SitterDelegation $delegation,
        public readonly User $actor,
        public readonly array $changedAttributes,
    ) {
        $this->delegation->loadMissing('owner', 'sitter');
    }
}
