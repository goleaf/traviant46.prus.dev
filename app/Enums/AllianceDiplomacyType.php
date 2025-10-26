<?php

declare(strict_types=1);

namespace App\Enums;

use function __;

enum AllianceDiplomacyType: string
{
    case NonAggression = 'non_aggression';
    case Confederation = 'confederation';
    case War = 'war';

    public function label(): string
    {
        return match ($this) {
            self::NonAggression => __('Non-aggression pact'),
            self::Confederation => __('Confederation'),
            self::War => __('War declaration'),
        };
    }
}
