<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\SitterPermission;

final class SitterRestriction
{
    public function __construct(
        public readonly string $action,
        public readonly bool $permitted,
        public readonly ?string $reason,
        public readonly SitterPermission $permission,
    ) {}

    public function isPermitted(): bool
    {
        return $this->permitted;
    }

    public function permissionKey(): string
    {
        return $this->permission->key();
    }

    /**
     * @return array{action: string, permitted: bool, reason: ?string, permission: string}
     */
    public function toArray(): array
    {
        return [
            'action' => $this->action,
            'permitted' => $this->permitted,
            'reason' => $this->reason,
            'permission' => $this->permissionKey(),
        ];
    }
}
