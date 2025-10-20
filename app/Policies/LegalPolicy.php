<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\StaffRole;
use App\Models\LegalDocument;
use App\Models\User;

class LegalPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canReviewLegal($user);
    }

    public function view(User $user, LegalDocument $document): bool
    {
        return $this->canReviewLegal($user);
    }

    public function create(User $user): bool
    {
        return $this->canReviewLegal($user);
    }

    public function update(User $user, LegalDocument $document): bool
    {
        return $this->canReviewLegal($user);
    }

    public function delete(User $user, LegalDocument $document): bool
    {
        return $this->canReviewLegal($user);
    }

    protected function canReviewLegal(User $user): bool
    {
        return $user->hasStaffRole(
            StaffRole::Admin,
            StaffRole::Legal,
        );
    }
}
