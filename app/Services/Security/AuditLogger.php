<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Enums\StaffRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class AuditLogger
{
    public function __construct(
        protected IpAnonymizer $anonymizer,
    ) {}

    /**
     * @param array<string, mixed> $metadata
     */
    public function log(?User $actor, string $action, array $metadata = [], ?Model $target = null, ?string $ipAddress = null): AuditLog
    {
        $actorId = $actor?->getKey();
        $actorRole = $actor?->staffRole()->value ?? null;
        if ($actor !== null && $actor->isMultihunter()) {
            $actorRole = StaffRole::Admin->value.'-multihunter';
        }

        $ip = $ipAddress;
        $ipHash = $ip !== null ? $this->anonymizer->anonymize($ip) : null;

        return AuditLog::create([
            'actor_id' => $actorId,
            'actor_username' => $actor?->username,
            'actor_role' => $actorRole,
            'action' => $action,
            'target_type' => $target?->getMorphClass(),
            'target_id' => $target?->getKey(),
            'ip_address' => $ip,
            'ip_address_hash' => $ipHash,
            'metadata' => $metadata,
            'performed_at' => Carbon::now(),
        ]);
    }
}
