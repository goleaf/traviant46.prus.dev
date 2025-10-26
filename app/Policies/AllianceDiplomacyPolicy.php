<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Alliance;
use App\Models\AllianceDiplomacy;
use App\Models\User;

class AllianceDiplomacyPolicy
{
    public function create(User $user, Alliance $alliance): bool
    {
        return app(AlliancePolicy::class)->manageDiplomacy($user, $alliance);
    }

    public function respond(User $user, AllianceDiplomacy $offer): bool
    {
        return app(AlliancePolicy::class)->manageDiplomacy($user, $offer->target);
    }

    public function cancel(User $user, AllianceDiplomacy $offer): bool
    {
        return app(AlliancePolicy::class)->manageDiplomacy($user, $offer->alliance);
    }
}
