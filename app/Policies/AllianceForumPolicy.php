<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Alliance;
use App\Models\AllianceForum;
use App\Models\User;
use App\Support\Auth\SitterContext;

class AllianceForumPolicy
{
    public function view(User $user, AllianceForum $forum): bool
    {
        if (! $user->alliance || $forum->alliance_id !== $user->alliance->getKey()) {
            return false;
        }

        if ($forum->moderators_only) {
            return app(AlliancePolicy::class)->moderateForums($user, $forum->alliance);
        }

        if (! $forum->visible_to_sitters && SitterContext::isActingAsSitter()) {
            return false;
        }

        return app(AlliancePolicy::class)->view($user, $forum->alliance);
    }

    public function create(User $user, Alliance $alliance): bool
    {
        return app(AlliancePolicy::class)->moderateForums($user, $alliance);
    }

    public function update(User $user, AllianceForum $forum): bool
    {
        return app(AlliancePolicy::class)->moderateForums($user, $forum->alliance);
    }

    public function delete(User $user, AllianceForum $forum): bool
    {
        return app(AlliancePolicy::class)->moderateForums($user, $forum->alliance);
    }

    public function reorder(User $user, Alliance $alliance): bool
    {
        return app(AlliancePolicy::class)->moderateForums($user, $alliance);
    }
}
