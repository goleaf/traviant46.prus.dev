<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\SitterPermission;
use App\Models\LoginActivity;
use App\Models\MultiAccountAlert;
use App\Models\SitterDelegation;
use App\Models\User;
use App\Services\Auth\ImpersonationManager;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Component;

class Dashboard extends Component
{
    /** @var list<array<string, mixed>> */
    public array $summaryCards = [];

    /** @var list<array<string, mixed>> */
    public array $secondaryCards = [];

    /** @var list<array<string, mixed>> */
    public array $recentUsers = [];

    /** @var list<array<string, mixed>> */
    public array $sessions = [];

    /** @var list<array<string, mixed>> */
    public array $sitterDelegations = [];

    /** @var list<array<string, mixed>> */
    public array $alerts = [];

    /** @var list<array<string, mixed>> */
    public array $auditTrail = [];

    public string $refreshedAt = '';

    public function mount(): void
    {
        $this->refreshData();
    }

    public function startImpersonation(int $userId): void
    {
        $admin = Auth::user();

        if (! $admin instanceof User || ! $admin->isAdmin()) {
            abort(403);
        }

        if ($admin->getKey() === $userId) {
            $this->addError('impersonation', __('You are already operating as this account.'));

            return;
        }

        $target = User::query()->findOrFail($userId);

        try {
            app(ImpersonationManager::class)->start($admin, $target, [
                'source' => 'admin.dashboard',
            ]);
        } catch (InvalidArgumentException $exception) {
            $this->addError('impersonation', $exception->getMessage());

            return;
        }

        $this->redirectRoute('home');
    }

    public function refreshData(): void
    {
        $now = Carbon::now();
        $timezone = config('app.timezone');

        $totalUsers = User::query()->count();
        $verifiedUsers = User::query()->whereNotNull('email_verified_at')->count();
        $twoFactorEnabled = User::query()->whereNotNull('two_factor_secret')->count();
        $bannedUsers = User::query()->where('is_banned', true)->count();
        $activeDelegations = SitterDelegation::query()->active($now)->count();
        $alertsCount = MultiAccountAlert::query()->count();

        $sessionRows = DB::table('sessions')
            ->orderByDesc('last_activity')
            ->limit(10)
            ->get(['id', 'user_id', 'ip_address', 'user_agent', 'last_activity']);

        $sessionUserIds = $sessionRows
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values();

        $sessionUsers = User::query()
            ->whereIn('id', $sessionUserIds)
            ->get(['id', 'username', 'legacy_uid'])
            ->keyBy('id');

        $liveSessionsCount = $sessionRows->count();

        $this->summaryCards = [
            [
                'label' => __('Registered players'),
                'value' => $totalUsers,
                'icon' => 'user-group',
                'color' => 'sky',
                'description' => __('World accounts across all tribes.'),
            ],
            [
                'label' => __('Live sessions'),
                'value' => $liveSessionsCount,
                'icon' => 'wifi',
                'color' => 'emerald',
                'description' => __('Browser sessions active in the past hour.'),
            ],
            [
                'label' => __('Active sitter links'),
                'value' => $activeDelegations,
                'icon' => 'user-plus',
                'color' => 'violet',
                'description' => __('Delegations that have not yet expired.'),
            ],
            [
                'label' => __('Multi-account alerts'),
                'value' => $alertsCount,
                'icon' => 'exclamation-triangle',
                'color' => 'orange',
                'description' => __('Investigations flagged by overlapping IP usage.'),
            ],
        ];

        $this->secondaryCards = [
            [
                'label' => __('Verified emails'),
                'value' => $verifiedUsers,
                'icon' => 'envelope-open',
                'color' => 'cyan',
                'description' => __('Players with verified contact methods.'),
            ],
            [
                'label' => __('Two-factor enabled'),
                'value' => $twoFactorEnabled,
                'icon' => 'shield-check',
                'color' => 'teal',
                'description' => __('Accounts protected by passkeys or TOTP.'),
            ],
            [
                'label' => __('Banned players'),
                'value' => $bannedUsers,
                'icon' => 'no-symbol',
                'color' => 'rose',
                'description' => __('Accounts currently suspended from play.'),
            ],
        ];

        $this->recentUsers = User::query()
            ->latest('created_at')
            ->limit(8)
            ->get([
                'id',
                'username',
                'email',
                'legacy_uid',
                'created_at',
                'is_banned',
                'email_verified_at',
            ])
            ->map(static function (User $user) use ($timezone, $now): array {
                $createdAt = optional($user->created_at)?->timezone($timezone);

                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'legacy_uid' => $user->legacy_uid,
                    'status' => $user->is_banned ? __('Banned') : ($user->hasVerifiedEmail() ? __('Verified') : __('Unverified')),
                    'status_color' => $user->is_banned ? 'red' : ($user->hasVerifiedEmail() ? 'emerald' : 'amber'),
                    'created_at' => $createdAt?->toDayDateTimeString() ?? __('Unknown'),
                    'created_diff' => $createdAt?->diffForHumans($now, Carbon::DIFF_ABSOLUTE, true) ?? __('n/a'),
                ];
            })
            ->all();

        $this->sessions = $sessionRows
            ->map(static function ($session) use ($sessionUsers, $timezone, $now): array {
                $user = $session->user_id !== null ? $sessionUsers->get($session->user_id) : null;
                $lastActivity = Carbon::createFromTimestamp((int) $session->last_activity)->timezone($timezone);

                return [
                    'id' => $session->id,
                    'user' => $user ? [
                        'id' => $user->id,
                        'username' => $user->username,
                        'legacy_uid' => $user->legacy_uid,
                    ] : null,
                    'ip' => $session->ip_address,
                    'agent' => Str::limit((string) $session->user_agent, 80),
                    'last_activity' => $lastActivity->toDayDateTimeString(),
                    'last_diff' => $lastActivity->diffForHumans($now, Carbon::DIFF_ABSOLUTE, true),
                ];
            })
            ->all();

        $this->sitterDelegations = SitterDelegation::query()
            ->with(['owner:id,username,legacy_uid', 'sitter:id,username,legacy_uid'])
            ->active($now)
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(static function (SitterDelegation $delegation) use ($timezone, $now): array {
                $expiresAt = optional($delegation->expires_at)?->timezone($timezone);
                $updatedAt = optional($delegation->updated_at)?->timezone($timezone);

                $permissionLabels = collect($delegation->permissions->toArray())
                    ->map(static fn (string $key): string => SitterPermission::fromKey($key)?->label() ?? Str::headline($key))
                    ->all();

                return [
                    'id' => $delegation->id,
                    'owner' => [
                        'id' => optional($delegation->owner)->id,
                        'username' => optional($delegation->owner)->username,
                        'legacy_uid' => optional($delegation->owner)->legacy_uid,
                    ],
                    'sitter' => [
                        'id' => optional($delegation->sitter)->id,
                        'username' => optional($delegation->sitter)->username,
                        'legacy_uid' => optional($delegation->sitter)->legacy_uid,
                    ],
                    'permissions' => $permissionLabels,
                    'expires_at' => $expiresAt?->toDayDateTimeString(),
                    'expires_diff' => $expiresAt?->diffForHumans($now, Carbon::DIFF_ABSOLUTE, true),
                    'updated_at' => $updatedAt?->toDayDateTimeString(),
                ];
            })
            ->all();

        $this->alerts = MultiAccountAlert::query()
            ->with(['primaryUser:id,username,legacy_uid', 'conflictUser:id,username,legacy_uid'])
            ->orderByDesc('last_seen_at')
            ->limit(8)
            ->get()
            ->map(static function (MultiAccountAlert $alert) use ($timezone, $now): array {
                $lastSeen = optional($alert->last_seen_at)?->timezone($timezone);

                return [
                    'id' => $alert->id,
                    'ip' => $alert->ip_address,
                    'primary_user' => [
                        'id' => optional($alert->primaryUser)->id,
                        'username' => optional($alert->primaryUser)->username,
                        'legacy_uid' => optional($alert->primaryUser)->legacy_uid,
                    ],
                    'conflict_user' => [
                        'id' => optional($alert->conflictUser)->id,
                        'username' => optional($alert->conflictUser)->username,
                        'legacy_uid' => optional($alert->conflictUser)->legacy_uid,
                    ],
                    'occurrences' => $alert->occurrences,
                    'last_seen_at' => $lastSeen?->toDayDateTimeString(),
                    'last_seen_diff' => $lastSeen?->diffForHumans($now, Carbon::DIFF_ABSOLUTE, true),
                ];
            })
            ->all();

        $this->auditTrail = LoginActivity::query()
            ->with(['user:id,username,legacy_uid', 'actingSitter:id,username,legacy_uid'])
            ->latest('created_at')
            ->limit(12)
            ->get()
            ->map(static function (LoginActivity $activity) use ($timezone, $now): array {
                $timestamp = optional($activity->created_at)?->timezone($timezone);

                return [
                    'id' => $activity->id,
                    'user' => [
                        'id' => optional($activity->user)->id,
                        'username' => optional($activity->user)->username,
                        'legacy_uid' => optional($activity->user)->legacy_uid,
                    ],
                    'actor' => $activity->actingSitter ? [
                        'id' => $activity->actingSitter->id,
                        'username' => $activity->actingSitter->username,
                        'legacy_uid' => $activity->actingSitter->legacy_uid,
                    ] : null,
                    'ip' => $activity->ip_address,
                    'via_sitter' => (bool) $activity->via_sitter,
                    'timestamp' => $timestamp?->toDayDateTimeString(),
                    'diff' => $timestamp?->diffForHumans($now, Carbon::DIFF_ABSOLUTE, true),
                ];
            })
            ->all();

        $this->refreshedAt = $now->diffForHumans(null, Carbon::DIFF_ABSOLUTE, true, 2);
    }

    public function render(): View
    {
        return view('livewire.admin.dashboard')
            ->layout('layouts.app');
    }
}
