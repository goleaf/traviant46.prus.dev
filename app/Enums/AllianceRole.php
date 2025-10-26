<?php

declare(strict_types=1);

namespace App\Enums;

use function __;

enum AllianceRole: string
{
    case Leader = 'leader';
    case Councillor = 'councillor';
    case Diplomat = 'diplomat';
    case Moderator = 'moderator';
    case Recruiter = 'recruiter';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Leader => __('Leader'),
            self::Councillor => __('Councillor'),
            self::Diplomat => __('Diplomat'),
            self::Moderator => __('Moderator'),
            self::Recruiter => __('Recruiter'),
            self::Member => __('Member'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Leader => 'amber',
            self::Councillor => 'violet',
            self::Diplomat => 'sky',
            self::Moderator => 'emerald',
            self::Recruiter => 'indigo',
            self::Member => 'slate',
        };
    }

    public function canManageProfile(): bool
    {
        return match ($this) {
            self::Leader,
            self::Councillor => true,
            default => false,
        };
    }

    public function canManageMembers(): bool
    {
        return match ($this) {
            self::Leader,
            self::Councillor => true,
            default => false,
        };
    }

    public function canManageDiplomacy(): bool
    {
        return match ($this) {
            self::Leader,
            self::Diplomat => true,
            default => false,
        };
    }

    public function canModerateForums(): bool
    {
        return match ($this) {
            self::Leader,
            self::Councillor,
            self::Moderator => true,
            default => false,
        };
    }

    public function canRecruit(): bool
    {
        return match ($this) {
            self::Leader,
            self::Councillor,
            self::Recruiter => true,
            default => false,
        };
    }
}
