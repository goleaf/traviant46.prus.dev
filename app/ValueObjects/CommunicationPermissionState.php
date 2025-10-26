<?php

declare(strict_types=1);

namespace App\ValueObjects;

final class CommunicationPermissionState
{
    public function __construct(
        public readonly bool $canManageMessages,
        public readonly bool $canManageArchives,
        public readonly bool $actingAsSitter,
        public readonly ?int $actingSitterId = null,
    ) {}

    public static function owner(): self
    {
        return new self(true, true, false, null);
    }

    public static function deniedForSitter(?int $sitterId = null): self
    {
        return new self(false, false, true, $sitterId);
    }

    public function canPerformBulkMessageActions(): bool
    {
        return $this->canManageMessages || $this->canManageArchives;
    }

    public function canArchive(): bool
    {
        return $this->canManageArchives;
    }
}
