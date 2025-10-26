<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\Activation;
use App\Models\User;

class LegacyLoginResult
{
    public const MODE_OWNER = 'owner';

    public const MODE_SITTER = 'sitter';

    public const MODE_ACTIVATION = 'activation';

    public function __construct(
        public readonly string $mode,
        public readonly ?User $user = null,
        public readonly ?User $sitter = null,
        public readonly ?Activation $activation = null,
    ) {}

    public static function owner(User $user): self
    {
        return new self(self::MODE_OWNER, $user);
    }

    public static function sitter(User $user, User $sitter): self
    {
        return new self(self::MODE_SITTER, $user, $sitter);
    }

    public static function activation(Activation $activation): self
    {
        return new self(self::MODE_ACTIVATION, null, null, $activation);
    }

    public function successful(): bool
    {
        return $this->mode === self::MODE_OWNER || $this->mode === self::MODE_SITTER;
    }

    public function viaSitter(): bool
    {
        return $this->mode === self::MODE_SITTER;
    }
}
