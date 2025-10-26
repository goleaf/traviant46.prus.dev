<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AlliancePost;
use App\Models\AllianceTopic;
use App\Models\User;

class AlliancePostPolicy
{
    public function view(User $user, AlliancePost $post): bool
    {
        return app(AllianceTopicPolicy::class)->view($user, $post->topic);
    }

    public function create(User $user, AllianceTopic $topic): bool
    {
        return app(AllianceTopicPolicy::class)->reply($user, $topic);
    }

    public function update(User $user, AlliancePost $post): bool
    {
        if ($post->author_id === $user->getKey()) {
            return app(AlliancePolicy::class)->post($user, $post->alliance);
        }

        return app(AlliancePolicy::class)->moderateForums($user, $post->alliance);
    }

    public function delete(User $user, AlliancePost $post): bool
    {
        if ($post->author_id === $user->getKey()) {
            return app(AlliancePolicy::class)->post($user, $post->alliance);
        }

        return app(AlliancePolicy::class)->moderateForums($user, $post->alliance);
    }
}
