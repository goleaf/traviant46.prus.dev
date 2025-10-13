<?php

namespace App\Enums;

class SitterPermission
{
    public const VIEW_VILLAGE = 'view_village';
    public const SWITCH_VILLAGE = 'switch_village';
    public const MANAGE_VILLAGE = 'manage_village';
    public const SEND_TROOPS = 'send_troops';
    public const MANAGE_TRADES = 'manage_trades';
    public const MANAGE_MESSAGES = 'manage_messages';
    public const SPEND_GOLD = 'spend_gold';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW_VILLAGE,
            self::SWITCH_VILLAGE,
            self::MANAGE_VILLAGE,
            self::SEND_TROOPS,
            self::MANAGE_TRADES,
            self::MANAGE_MESSAGES,
            self::SPEND_GOLD,
        ];
    }
}
