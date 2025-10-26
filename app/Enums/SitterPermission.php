<?php

namespace App\Enums;

enum SitterPermission: string
{
    case Raid = 'army.raid';
    case Reinforce = 'army.reinforce';
    case SendResources = 'economy.send_resources';
    case SpendGold = 'premium.spend_gold';
    case ManageMessages = 'communication.manage_messages';
    case ManageArchives = 'communication.manage_archives';
    case AllianceContribute = 'alliance.contribute';

    public function label(): string
    {
        return match ($this) {
            self::Raid => __('Launch raids and attacks'),
            self::Reinforce => __('Send defensive reinforcements'),
            self::SendResources => __('Transfer resources via marketplace'),
            self::SpendGold => __('Buy or spend Travian Gold'),
            self::ManageMessages => __('Send and answer messages'),
            self::ManageArchives => __('Delete or archive messages and reports'),
            self::AllianceContribute => __('Contribute to alliance bonuses'),
        };
    }

    public function legacyFlag(): int
    {
        return match ($this) {
            self::Raid => 1,
            self::Reinforce => 2,
            self::SendResources => 4,
            self::SpendGold => 8,
            self::ManageMessages => 16,
            self::ManageArchives => 32,
            self::AllianceContribute => 64,
        };
    }
}
