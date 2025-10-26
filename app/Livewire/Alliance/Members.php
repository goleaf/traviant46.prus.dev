<?php

declare(strict_types=1);

namespace App\Livewire\Alliance;

use App\Enums\AllianceRole;
use App\Enums\SitterPermission;
use App\Models\Alliance;
use App\Models\AllianceMember;
use App\Models\User;
use App\Support\Auth\SitterContext;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Displays alliance roster with role badges, sitter awareness alerts, and moderator tools.
 */
class Members extends Component
{
    use AuthorizesRequests;

    public Alliance $alliance;

    public ?AllianceMember $membership = null;

    public bool $showSitterWarning = false;

    /**
     * @var array<int, string>
     */
    public array $availableRoles = [];

    public function mount(?Alliance $alliance = null): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        $resolvedAlliance = $alliance ?? $user->alliance;

        if (! $resolvedAlliance instanceof Alliance) {
            abort(404);
        }

        $this->alliance = $resolvedAlliance;

        $this->authorize('view', $this->alliance);

        $this->membership = $this->alliance->membershipFor($user);
        $this->availableRoles = array_map(
            static fn (AllianceRole $role): string => $role->value,
            AllianceRole::cases(),
        );

        $this->showSitterWarning = SitterContext::isActingAsSitter()
            && ! SitterContext::hasPermission($user, SitterPermission::AllianceContribute);
    }

    public function promote(int $memberId, string $roleValue): void
    {
        $this->authorize('manageMembers', $this->alliance);

        $role = AllianceRole::tryFrom($roleValue);

        if (! $role instanceof AllianceRole) {
            $this->addError('role', __('Invalid role selection.'));

            return;
        }

        $target = $this->alliance->members()->with('user')->findOrFail($memberId);

        if ((int) $target->user_id === (int) $this->alliance->founder_id && $role !== AllianceRole::Leader) {
            $this->addError('role', __('The founder must remain the alliance leader.'));

            return;
        }

        if ($target->role === $role) {
            return;
        }

        $target->role = $role;
        $target->save();

        if ($this->membership && $this->membership->is($target)) {
            $this->membership = $target->fresh();
        }

        $this->dispatch('alliance-members-updated');
    }

    public function render(): View
    {
        $members = $this->alliance->members()
            ->with('user')
            ->get()
            ->sortBy(fn (AllianceMember $member) => $this->roleWeight($member->role));

        return view('livewire.alliance.members', [
            'members' => $members,
            'canManageMembers' => optional($this->membership)?->canManageMembers() && ! $this->showSitterWarning,
            'roleOptions' => AllianceRole::cases(),
        ]);
    }

    private function roleWeight(AllianceRole|int|string|null $role): int
    {
        $role = $role instanceof AllianceRole ? $role : AllianceRole::tryFrom((string) $role);

        return match ($role) {
            AllianceRole::Leader => 0,
            AllianceRole::Councillor => 1,
            AllianceRole::Diplomat => 2,
            AllianceRole::Moderator => 3,
            AllianceRole::Recruiter => 4,
            default => 5,
        };
    }
}
