<?php

namespace App\Livewire\Dashboard;

use App\Models\LoginActivity;
use App\Models\SitterAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Livewire\Component;

class Overview extends Component
{
    /** @var array<string, mixed> */
    public array $metrics = [];

    public string $refreshedAt = '';

    public function mount(): void
    {
        $this->refreshMetrics();
    }

    public function refreshMetrics(): void
    {
        $user = Auth::user();

        if (! $user) {
            $this->metrics = [];
            $this->refreshedAt = Carbon::now()->toDateTimeString();

            return;
        }

        $activeAssignments = SitterAssignment::query()
            ->forAccount($user)
            ->active(Date::now())
            ->count();

        $delegatedAccounts = SitterAssignment::query()
            ->forSitter($user)
            ->active(Date::now())
            ->count();

        $recentLogins = LoginActivity::query()
            ->forUser($user)
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (LoginActivity $activity) => [
                'id' => $activity->id,
                'ip' => $activity->ip_address,
                'via_sitter' => $activity->via_sitter,
                'timestamp' => optional($activity->created_at)?->timezone($user->timezone ?? config('app.timezone'))
                    ->isoFormat('MMM D, YYYY • HH:mm'),
            ])
            ->all();

        $this->metrics = [
            'activeSitters' => $activeAssignments,
            'delegatedAccounts' => $delegatedAccounts,
            'twoFactorEnabled' => (bool) $user->two_factor_secret,
            'recentLogins' => $recentLogins,
        ];

        $this->refreshedAt = Carbon::now()->diffForHumans(null, Carbon::DIFF_ABSOLUTE, true, 2);
    }

    public function render(): View
    {
        return view('livewire.dashboard.overview');
    }
}
