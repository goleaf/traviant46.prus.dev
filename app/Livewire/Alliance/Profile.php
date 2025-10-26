<?php

declare(strict_types=1);

namespace App\Livewire\Alliance;

use App\Enums\SitterPermission;
use App\Models\Alliance;
use App\Models\AllianceMember;
use App\Models\User;
use App\Support\Auth\SitterContext;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Rule;
use Livewire\Component;

/**
 * Presents the alliance profile, description editor, and sitter awareness warnings.
 */
class Profile extends Component
{
    use AuthorizesRequests;

    public Alliance $alliance;

    public ?AllianceMember $membership = null;

    #[Rule('required|string|max:4000')]
    public string $description = '';

    #[Rule('nullable|string|max:1000')]
    public string $messageOfDay = '';

    public bool $showSitterWarning = false;

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

        $this->alliance = $resolvedAlliance->loadMissing('founder');

        $this->authorize('view', $this->alliance);

        $this->membership = $this->alliance->membershipFor($user);
        $this->description = (string) ($this->alliance->description ?? '');
        $this->messageOfDay = (string) ($this->alliance->message_of_day ?? '');

        $this->showSitterWarning = SitterContext::isActingAsSitter()
            && ! SitterContext::hasPermission($user, SitterPermission::AllianceContribute);
    }

    public function saveProfile(): void
    {
        $this->authorize('update', $this->alliance);

        $validated = $this->validate();

        $this->alliance->forceFill([
            'description' => $validated['description'],
            'message_of_day' => $validated['messageOfDay'] ?? null,
        ])->save();

        $this->dispatch('alliance-profile-updated');
    }

    public function render(): View
    {
        $alliance = $this->alliance->loadMissing([
            'founder',
            'members.user',
        ]);

        return view('livewire.alliance.profile', [
            'alliance' => $alliance,
            'membership' => $this->membership,
            'memberCount' => $alliance->members->count(),
        ]);
    }
}
