<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Game\MovementOrder;
use App\Models\Game\Village;
use App\Models\LoginActivity;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class PlayerAudit extends Component
{
    #[Validate('required|string|min:2|max:120')]
    public string $lookup = '';

    /** @var list<array{id: int, username: string, legacy_uid: int|null, email: string|null}> */
    public array $matches = [];

    /** @var array<string, mixed>|null */
    public ?array $player = null;

    /** @var list<array<string, mixed>> */
    public array $villages = [];

    /** @var list<array<string, mixed>> */
    public array $sessions = [];

    /** @var list<array<string, mixed>> */
    public array $ipAddresses = [];

    /** @var list<array<string, mixed>> */
    public array $loginActivity = [];

    /** @var list<array<string, mixed>> */
    public array $movements = [];

    public ?string $auditReport = null;

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public function lookupPlayer(): void
    {
        $this->resetAuditState();

        if (trim($this->lookup) === '') {
            $this->errorMessage = __('Enter a username, UID, or email to begin the audit.');

            return;
        }

        $this->validateOnly('lookup');

        $term = trim($this->lookup);
        $matches = $this->searchPlayers($term);

        if ($matches->isEmpty()) {
            $this->errorMessage = __('No players matched ":term". Refine your search.', ['term' => $term]);

            return;
        }

        if ($matches->count() === 1) {
            $this->hydrateAudit($matches->first());

            return;
        }

        $this->matches = $matches
            ->map(static function (User $user): array {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'legacy_uid' => $user->legacy_uid,
                    'email' => $user->email,
                ];
            })
            ->all();

        $this->statusMessage = __('Multiple players matched your query. Select one below to generate the audit output.');
    }

    public function selectUser(int $userId): void
    {
        $this->resetAuditState();

        $user = User::query()
            ->select([
                'id',
                'legacy_uid',
                'username',
                'email',
                'created_at',
                'is_banned',
                'ban_reason',
                'ban_expires_at',
                'last_login_at',
                'last_login_ip',
                'tribe',
                'race',
            ])
            ->findOrFail($userId);

        $this->hydrateAudit($user);
    }

    public function render(): View
    {
        return view('livewire.admin.player-audit')->layout('layouts.app');
    }

    protected function hydrateAudit(User $user): void
    {
        $timezone = $user->timezone ?? config('app.timezone', 'UTC');

        $this->player = [
            'id' => $user->id,
            'legacy_uid' => $user->legacy_uid,
            'username' => $user->username,
            'email' => $user->email,
            'tribe' => $user->tribe_name ?? __('Unknown'),
            'created_at' => optional($user->created_at)?->timezone($timezone)?->toDayDateTimeString() ?? __('Unknown'),
            'last_login_at' => optional($user->last_login_at)?->timezone($timezone)?->toDayDateTimeString(),
            'last_login_ip' => $user->last_login_ip,
            'is_banned' => (bool) $user->is_banned,
            'ban_reason' => $user->ban_reason,
            'ban_expires_at' => optional($user->ban_expires_at)?->timezone($timezone)?->toDayDateTimeString(),
        ];

        $this->villages = $this->loadVillages($user, $timezone);
        $this->sessions = $this->loadSessions($user, $timezone);
        $loginActivities = $this->loadLoginActivities($user, $timezone);
        $this->loginActivity = $loginActivities;
        $this->ipAddresses = $this->summarizeIpAddresses($loginActivities, $this->sessions);
        $this->movements = $this->loadMovements($user, $timezone);
        $this->matches = [];
        $this->statusMessage = __('Audit generated for :player.', ['player' => $user->username]);
        $this->auditReport = $this->buildReport(
            $user,
            $timezone,
            $this->villages,
            $this->sessions,
            $this->ipAddresses,
            $this->movements,
            $this->loginActivity,
        );
    }

    /**
     * @return Collection<int, User>
     */
    protected function searchPlayers(string $term): Collection
    {
        $query = User::query()
            ->select(['id', 'legacy_uid', 'username', 'email'])
            ->limit(15);

        $normalized = Str::replace(['#', ' '], '', $term);
        $numericTerm = ctype_digit($normalized) ? (int) $normalized : null;

        $query->where(function ($builder) use ($term, $numericTerm): void {
            $builder->where('username', 'like', '%'.$term.'%')
                ->orWhere('email', 'like', '%'.$term.'%');

            if ($numericTerm !== null) {
                $builder->orWhere('legacy_uid', $numericTerm)
                    ->orWhere('id', $numericTerm);
            }
        });

        return $query->orderBy('username')->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function loadVillages(User $user, string $timezone): array
    {
        /** @var EloquentCollection<int, Village> $villages */
        $villages = $user->villages()
            ->select([
                'id',
                'legacy_kid',
                'name',
                'x_coordinate',
                'y_coordinate',
                'population',
                'loyalty',
                'village_category',
                'is_capital',
                'founded_at',
            ])
            ->orderByDesc('population')
            ->get();

        return $villages->map(static function (Village $village) use ($timezone): array {
            $foundedAt = optional($village->founded_at)?->timezone($timezone);

            return [
                'id' => $village->id,
                'legacy_kid' => $village->legacy_kid,
                'name' => $village->name ?? __('Unnamed village'),
                'coordinates' => sprintf('%d|%d', $village->x_coordinate, $village->y_coordinate),
                'population' => (int) $village->population,
                'loyalty' => (int) $village->loyalty,
                'is_capital' => (bool) $village->is_capital,
                'category' => $village->village_category,
                'founded_at' => $foundedAt?->toDayDateTimeString(),
                'founded_diff' => $foundedAt?->diffForHumans(null, Carbon::DIFF_ABSOLUTE, true, 1),
            ];
        })->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function loadSessions(User $user, string $timezone): array
    {
        /** @var EloquentCollection<int, UserSession> $sessions */
        $sessions = $user->sessions()
            ->select([
                'id',
                'ip_address',
                'user_agent',
                'last_activity_at',
                'expires_at',
            ])
            ->orderByDesc('last_activity_at')
            ->limit(10)
            ->get();

        return $sessions->map(static function (UserSession $session) use ($timezone): array {
            $lastActivity = optional($session->last_activity_at)?->timezone($timezone);
            $expiresAt = optional($session->expires_at)?->timezone($timezone);

            return [
                'id' => $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_activity' => $lastActivity?->toDayDateTimeString(),
                'last_activity_diff' => $lastActivity?->diffForHumans(null, Carbon::DIFF_ABSOLUTE, true, 1),
                // Preserve a timestamp value so IP grouping can compare recency.
                'last_activity_timestamp' => $lastActivity?->timestamp ?? 0,
                'expires_at' => $expiresAt?->toDayDateTimeString(),
            ];
        })->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function loadLoginActivities(User $user, string $timezone): array
    {
        /** @var EloquentCollection<int, LoginActivity> $activities */
        $activities = LoginActivity::query()
            ->forUser($user)
            ->select([
                'id',
                'ip_address',
                'ip_address_hash',
                'user_agent',
                'logged_at',
                'via_sitter',
                'acting_sitter_id',
                'geo',
            ])
            ->with([
                'actingSitter:id,username',
            ])
            ->latest('logged_at')
            ->limit(25)
            ->get();

        return $activities->map(static function (LoginActivity $activity) use ($timezone): array {
            $loggedAt = optional($activity->logged_at)?->timezone($timezone);
            $location = Arr::get($activity->geo ?? [], 'city');
            $country = Arr::get($activity->geo ?? [], 'country');

            $locationString = null;

            if ($location && $country) {
                $locationString = $location.', '.$country;
            } elseif ($country) {
                $locationString = $country;
            } elseif ($location) {
                $locationString = $location;
            }

            return [
                'id' => $activity->id,
                'ip_address' => $activity->ip_address,
                'ip_address_hash' => $activity->ip_address_hash,
                'user_agent' => $activity->user_agent,
                'logged_at' => $loggedAt?->toDayDateTimeString(),
                'logged_at_diff' => $loggedAt?->diffForHumans(null, Carbon::DIFF_ABSOLUTE, true, 1),
                'logged_at_timestamp' => $loggedAt?->timestamp ?? 0,
                'via_sitter' => (bool) $activity->via_sitter,
                'sitter' => $activity->actingSitter?->username,
                'location' => $locationString,
            ];
        })->all();
    }

    /**
     * @param list<array<string, mixed>> $loginActivities
     * @param list<array<string, mixed>> $sessions
     * @return list<array<string, mixed>>
     */
    protected function summarizeIpAddresses(array $loginActivities, array $sessions): array
    {
        // Merge login activity IPs with currently active session IPs so that the
        // audit always surfaces the latest entry even when login retention has
        // expired. Sessions are treated as virtual login rows during grouping.
        $sessionActivity = collect($sessions)
            ->filter(static fn (array $session): bool => filled($session['ip_address'] ?? null))
            ->map(static function (array $session): array {
                return [
                    'ip_address' => $session['ip_address'],
                    'ip_address_hash' => null,
                    'via_sitter' => false,
                    'logged_at' => $session['last_activity'] ?? null,
                    'logged_at_diff' => $session['last_activity_diff'] ?? null,
                    'logged_at_timestamp' => $session['last_activity_timestamp'] ?? 0,
                ];
            });

        return collect($loginActivities)
            ->concat($sessionActivity)
            ->groupBy(function (array $activity): string {
                if ($activity['ip_address'] !== null && $activity['ip_address'] !== '') {
                    return 'ip:'.$activity['ip_address'];
                }

                if ($activity['ip_address_hash'] !== null && $activity['ip_address_hash'] !== '') {
                    return 'hash:'.$activity['ip_address_hash'];
                }

                return 'unknown';
            })
            ->map(function (Collection $group): array {
                /** @var array<string, mixed> $latest */
                $latest = $group->sortByDesc('logged_at_timestamp')->first() ?? [];

                $display = $latest['ip_address'] ?? null;

                if ($display === null && isset($latest['ip_address_hash']) && $latest['ip_address_hash']) {
                    $display = 'hash:'.Str::limit((string) $latest['ip_address_hash'], 16, '');
                }

                return [
                    'display' => $display ?? __('Unknown'),
                    'ip_address' => $latest['ip_address'] ?? null,
                    'ip_address_hash' => $latest['ip_address_hash'] ?? null,
                    'uses' => $group->count(),
                    'via_sitter' => $group->filter(fn (array $entry): bool => (bool) $entry['via_sitter'])->count(),
                    'last_seen' => $latest['logged_at'] ?? __('Unknown'),
                    'last_seen_diff' => $latest['logged_at_diff'] ?? __('n/a'),
                    'sort' => $latest['logged_at_timestamp'] ?? 0,
                ];
            })
            ->sortByDesc('sort')
            ->values()
            ->map(static function (array $entry): array {
                unset($entry['sort']);

                return $entry;
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function loadMovements(User $user, string $timezone): array
    {
        /** @var EloquentCollection<int, MovementOrder> $movements */
        $movements = MovementOrder::query()
            ->forUser($user)
            ->select([
                'id',
                'movement_type',
                'mission',
                'status',
                'depart_at',
                'arrive_at',
                'return_at',
                'origin_village_id',
                'target_village_id',
            ])
            ->with([
                'originVillage:id,name,x_coordinate,y_coordinate',
                'targetVillage:id,name,x_coordinate,y_coordinate',
            ])
            ->orderByDesc('depart_at')
            ->orderByDesc('arrive_at')
            ->limit(12)
            ->get();

        return $movements->map(static function (MovementOrder $movement) use ($timezone): array {
            $departAt = optional($movement->depart_at)?->timezone($timezone);
            $arriveAt = optional($movement->arrive_at)?->timezone($timezone);
            $returnAt = optional($movement->return_at)?->timezone($timezone);

            $origin = $movement->originVillage;
            $target = $movement->targetVillage;

            return [
                'id' => $movement->id,
                'movement_type' => $movement->movement_type,
                'mission' => $movement->mission ?? null,
                'status' => $movement->status,
                'origin' => $origin ? sprintf(
                    '%s (%d|%d)',
                    $origin->name ?? __('Unknown'),
                    (int) $origin->x_coordinate,
                    (int) $origin->y_coordinate,
                ) : __('Unknown'),
                'target' => $target ? sprintf(
                    '%s (%d|%d)',
                    $target->name ?? __('Unknown'),
                    (int) $target->x_coordinate,
                    (int) $target->y_coordinate,
                ) : __('Unknown'),
                'depart_at' => $departAt?->toDayDateTimeString(),
                'arrive_at' => $arriveAt?->toDayDateTimeString(),
                'return_at' => $returnAt?->toDayDateTimeString(),
                'depart_diff' => $departAt?->diffForHumans(null, Carbon::DIFF_ABSOLUTE, true, 1),
                'arrive_diff' => $arriveAt?->diffForHumans(null, Carbon::DIFF_ABSOLUTE, true, 1),
            ];
        })->all();
    }

    protected function buildReport(
        User $user,
        string $timezone,
        array $villages,
        array $sessions,
        array $ipAddresses,
        array $movements,
        array $loginActivity,
    ): string {
        $lines = [];

        $lines[] = sprintf(
            'Player: %s (UID %s, ID %s)',
            $user->username,
            $user->legacy_uid ?? 'n/a',
            $user->id,
        );
        $lines[] = sprintf('Email: %s', $user->email ?? 'n/a');
        $lines[] = sprintf('Tribe: %s', $user->tribe_name ?? 'Unknown');
        $lines[] = sprintf('Created: %s', $this->player['created_at'] ?? 'Unknown');

        if (! empty($this->player['last_login_at'])) {
            $lines[] = sprintf('Last login: %s (IP %s)', $this->player['last_login_at'], $this->player['last_login_ip'] ?? 'n/a');
        }

        if (! empty($this->player['is_banned']) && ! empty($this->player['ban_reason'])) {
            $lines[] = sprintf('Ban: %s (until %s)', $this->player['ban_reason'], $this->player['ban_expires_at'] ?? 'n/a');
        }

        $lines[] = '';
        $lines[] = sprintf('Villages (%d):', count($villages));

        if ($villages === []) {
            $lines[] = ' - none';
        } else {
            foreach ($villages as $village) {
                $parts = [
                    $village['name'],
                    sprintf('kid:%s', $village['legacy_kid'] ?? 'n/a'),
                    sprintf('at %s', $village['coordinates']),
                    sprintf('pop %d', $village['population']),
                    sprintf('loyalty %d%%', $village['loyalty']),
                ];

                if (! empty($village['is_capital'])) {
                    $parts[] = 'capital';
                } elseif (! empty($village['category'])) {
                    $parts[] = (string) $village['category'];
                }

                if (! empty($village['founded_at'])) {
                    $parts[] = sprintf('founded %s', $village['founded_at']);
                }

                $lines[] = ' - '.implode('; ', array_filter($parts));
            }
        }

        $lines[] = '';
        $lines[] = sprintf('Sessions (%d):', count($sessions));

        if ($sessions === []) {
            $lines[] = ' - none active';
        } else {
            foreach ($sessions as $session) {
                $lines[] = sprintf(
                    ' - %s | IP %s | %s | expires %s',
                    $session['last_activity'] ?? 'unknown',
                    $session['ip_address'] ?? 'n/a',
                    $session['user_agent'] ?? 'n/a',
                    $session['expires_at'] ?? 'n/a',
                );
            }
        }

        $lines[] = '';
        $lines[] = sprintf('IP addresses (%d):', count($ipAddresses));

        if ($ipAddresses === []) {
            $lines[] = ' - no historical IP addresses stored';
        } else {
            foreach ($ipAddresses as $address) {
                $lines[] = sprintf(
                    ' - %s | uses %d | via sitter %d | last %s (%s)',
                    $address['display'],
                    $address['uses'],
                    $address['via_sitter'],
                    $address['last_seen'],
                    $address['last_seen_diff'],
                );
            }
        }

        $lines[] = '';
        $lines[] = sprintf('Recent movements (%d):', count($movements));

        if ($movements === []) {
            $lines[] = ' - no recorded movements';
        } else {
            foreach ($movements as $movement) {
                $mission = $movement['mission'] ? ' '.$movement['mission'] : '';

                $lines[] = sprintf(
                    ' - [%s%s] %s → %s | depart %s | arrive %s',
                    $movement['movement_type'],
                    $mission,
                    $movement['origin'],
                    $movement['target'],
                    $movement['depart_at'] ?? 'n/a',
                    $movement['arrive_at'] ?? 'n/a',
                );
            }
        }

        $lines[] = '';
        $lines[] = sprintf('Login activity (latest %d entries, timezone %s):', count($loginActivity), $timezone);

        if ($loginActivity === []) {
            $lines[] = ' - none recorded';
        } else {
            foreach (array_slice($loginActivity, 0, 10) as $activity) {
                $location = $activity['location'] ? ' @ '.$activity['location'] : '';
                $sitter = $activity['via_sitter'] ? ' via sitter'.($activity['sitter'] ? ' '.$activity['sitter'] : '') : '';

                $lines[] = sprintf(
                    ' - %s | IP %s%s%s',
                    $activity['logged_at'] ?? 'n/a',
                    $activity['ip_address'] ?? ($activity['ip_address_hash'] ? 'hash:'.Str::limit((string) $activity['ip_address_hash'], 16, '') : 'n/a'),
                    $location,
                    $sitter,
                );
            }
        }

        return implode(PHP_EOL, $lines);
    }

    protected function resetAuditState(): void
    {
        $this->matches = [];
        $this->player = null;
        $this->villages = [];
        $this->sessions = [];
        $this->ipAddresses = [];
        $this->loginActivity = [];
        $this->movements = [];
        $this->auditReport = null;
        $this->statusMessage = null;
        $this->errorMessage = null;
    }
}
