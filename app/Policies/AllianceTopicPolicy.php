<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\SitterPermission;
use App\Models\AllianceForum;
use App\Models\AllianceTopic;
use App\Models\User;
use App\Support\Auth\SitterContext;

class AllianceTopicPolicy
{
    public function view(User $user, AllianceTopic $topic): bool
    {
        return app(AllianceForumPolicy::class)->view($user, $topic->forum);
    }

    public function create(User $user, AllianceForum $forum): bool
    {
        if (! app(AlliancePolicy::class)->post($user, $forum->alliance)) {
            return false;
        }

        if ($forum->moderators_only) {
            return app(AlliancePolicy::class)->moderateForums($user, $forum->alliance);
        }

        return true;
    }

    public function update(User $user, AllianceTopic $topic): bool
    {
        if ($topic->author_id === $user->getKey()) {
            return SitterContext::hasPermission($user, SitterPermission::ManageMessages);
        }

        return app(AlliancePolicy::class)->moderateForums($user, $topic->alliance);
    }

    public function delete(User $user, AllianceTopic $topic): bool
    {
        return app(AlliancePolicy::class)->moderateForums($user, $topic->alliance);
    }

    public function pin(User $user, AllianceTopic $topic): bool
    {
        return app(AlliancePolicy::class)->moderateForums($user, $topic->alliance);
    }

    public function lock(User $user, AllianceTopic $topic): bool
    {
        return app(AlliancePolicy::class)->moderateForums($user, $topic->alliance);
    }

    public function reply(User $user, AllianceTopic $topic): bool
    {
        if ($topic->is_locked) {
            return false;
        }

        return app(AlliancePolicy::class)->post($user, $topic->alliance);
    }
}
