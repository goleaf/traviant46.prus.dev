<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Enums\StaffRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Context;

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
        $metadata = $this->appendActingMetadata($metadata, $actor);

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

    /**
     * @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    private function appendActingMetadata(array $metadata, ?User $actor): array
    {
        if (array_key_exists('acted_by', $metadata)) {
            return $metadata;
        }

        if (! Context::has('acting_as_sitter') || Context::get('acting_as_sitter') !== true) {
            return $metadata;
        }

        $actingSitterId = Context::has('acting_sitter_id') ? Context::get('acting_sitter_id') : null;
        $actingSitterId = is_numeric($actingSitterId) ? (int) $actingSitterId : null;
        $actingSitterUsername = Context::has('acting_sitter_username') ? Context::get('acting_sitter_username') : null;
        $actingSitterUsername = is_string($actingSitterUsername) ? $actingSitterUsername : null;

        $actedBy = array_filter([
            'id' => $actingSitterId,
            'username' => $actingSitterUsername,
        ], static fn ($value) => $value !== null && $value !== '');

        if ($actedBy === []) {
            return $metadata;
        }

        $metadata['acted_by'] = $actedBy;

        if (! array_key_exists('acting_on', $metadata)) {
            $ownerId = $actor?->getKey();
            if ($ownerId === null && Context::has('acting_owner_id')) {
                $actingOwnerId = Context::get('acting_owner_id');
                $ownerId = is_numeric($actingOwnerId) ? (int) $actingOwnerId : null;
            }

            $ownerUsername = $actor?->username;
            if ($ownerUsername === null && Context::has('acting_owner_username')) {
                $ownerUsername = Context::get('acting_owner_username');
                $ownerUsername = is_string($ownerUsername) ? $ownerUsername : null;
            }

            $actingOn = array_filter([
                'id' => $ownerId,
                'username' => $ownerUsername,
            ], static fn ($value) => $value !== null && $value !== '');

            if ($actingOn !== []) {
                $metadata['acting_on'] = $actingOn;
            }
        }

        return $metadata;
    }
}
