<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\MultiAccountAlertSeverity;
use App\Enums\MultiAccountAlertStatus;
use App\Models\MultiAccountAlert;
use App\Models\User;
use App\Services\Security\MultiAccountDetector;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class Alerts extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    protected string $pageName = 'alerts_page';

    #[Url(as: 'alerts_page')]
    public int $page = 1;

    #[Url(as: 'severity')]
    public ?string $severity = null;

    #[Url(as: 'status')]
    public ?string $status = null;

    #[Url(as: 'search')]
    #[Validate('nullable|string|max:255')]
    public ?string $search = null;

    #[Url(as: 'source_type')]
    public ?string $sourceType = null;

    #[Url(as: 'ip')]
    #[Validate('nullable|string|max:45')]
    public ?string $ip = null;

    #[Url(as: 'device_hash')]
    #[Validate('nullable|string|max:64')]
    public ?string $deviceHash = null;

    #[Validate('nullable|string|max:1000')]
    public ?string $notes = null;

    public ?int $actionAlertId = null;

    public ?string $actionType = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $actionContext = null;

    public function mount(): void
    {
        $this->authorize('viewAny', MultiAccountAlert::class);

        $this->severity = $this->normalizeSeverity($this->severity);
        $this->status = $this->normalizeStatus($this->status);
        $this->sourceType = $this->normalizeSourceType($this->sourceType);
        $this->search = $this->normalizeString($this->search);
        $this->ip = $this->normalizeString($this->ip);
        $this->deviceHash = $this->normalizeString($this->deviceHash);
    }

    #[Computed]
    public function severityOptions(): array
    {
        return MultiAccountAlertSeverity::cases();
    }

    #[Computed]
    public function statusOptions(): array
    {
        return MultiAccountAlertStatus::cases();
    }

    #[Computed]
    public function alerts(): LengthAwarePaginator
    {
        $query = MultiAccountAlert::query()
            ->with(['resolvedBy', 'dismissedBy'])
            ->orderByDesc('last_seen_at');

        if ($this->severity !== null) {
            $query->where('severity', $this->severity);
        }

        if ($this->status !== null) {
            $query->where('status', $this->status);
        }

        if ($this->sourceType !== null) {
            $query->where('source_type', $this->sourceType);
        }

        if ($this->ip !== null && $this->ip !== '') {
            $query->where('ip_address', 'like', $this->ip.'%');
        }

        if ($this->deviceHash !== null && $this->deviceHash !== '') {
            $query->where('device_hash', $this->deviceHash);
        }

        if ($this->search !== null && $this->search !== '') {
            $term = $this->search;

            $query->where(function ($inner) use ($term): void {
                $inner->where('ip_address', 'like', '%'.$term.'%')
                    ->orWhere('device_hash', 'like', '%'.$term.'%');

                if (is_numeric($term)) {
                    $inner->orWhereJsonContains('user_ids', (int) $term);
                }
            });
        }

        return $query->paginate(25, ['*'], $this->pageName, $this->page);
    }

    public function updatingSeverity(): void
    {
        $this->severity = $this->normalizeSeverity($this->severity);
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->status = $this->normalizeStatus($this->status);
        $this->resetPage();
    }

    public function updatingSourceType(): void
    {
        $this->sourceType = $this->normalizeSourceType($this->sourceType);
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->search = $this->normalizeString($this->search);
        $this->resetPage();
    }

    public function updatingIp(): void
    {
        $this->ip = $this->normalizeString($this->ip);
        $this->resetPage();
    }

    public function updatingDeviceHash(): void
    {
        $this->deviceHash = $this->normalizeString($this->deviceHash);
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->severity = null;
        $this->status = null;
        $this->search = null;
        $this->sourceType = null;
        $this->ip = null;
        $this->deviceHash = null;
        $this->resetPage();
    }

    public function confirmResolve(int $alertId): void
    {
        $this->prepareAction('resolve', $alertId);
    }

    public function confirmDismiss(int $alertId): void
    {
        $this->prepareAction('dismiss', $alertId);
    }

    public function performAction(MultiAccountDetector $detector): void
    {
        if ($this->actionAlertId === null || $this->actionType === null) {
            return;
        }

        $alert = MultiAccountAlert::query()->find($this->actionAlertId);

        if (! $alert instanceof MultiAccountAlert) {
            $this->resetActionState();

            return;
        }

        $this->authorize($this->actionType, $alert);

        $validated = $this->validate();
        $notes = $this->normalizeString($validated['notes'] ?? $this->notes);

        $actor = Auth::user();

        if (! $actor instanceof User) {
            abort(403);
        }

        if ($this->actionType === 'resolve') {
            $detector->resolveAlert($alert, $actor, $notes);
            $message = __('Alert marked as resolved. Notes saved to audit trail.');
        } else {
            $detector->dismissAlert($alert, $actor, $notes);
            $message = __('Alert dismissed. Notes saved to audit trail.');
        }

        session()->flash('status', $message);

        $this->resetActionState();
        $this->dispatch('admin.alerts:refresh');
    }

    public function cancelAction(): void
    {
        $this->resetActionState();
    }

    public function render(): View
    {
        return view('livewire.admin.alerts', [
            'alerts' => $this->alerts,
            'severityOptions' => $this->severityOptions,
            'statusOptions' => $this->statusOptions,
        ]);
    }

    protected function prepareAction(string $action, int $alertId): void
    {
        if (! in_array($action, ['resolve', 'dismiss'], true)) {
            return;
        }

        $alert = MultiAccountAlert::query()
            ->with(['resolvedBy', 'dismissedBy'])
            ->findOrFail($alertId);

        $this->authorize($action, $alert);

        $this->actionAlertId = $alert->getKey();
        $this->actionType = $action;
        $this->notes = null;

        $identifier = $alert->source_type === 'device'
            ? $alert->device_hash
            : $alert->ip_address;

        $this->actionContext = [
            'id' => $alert->getKey(),
            'source_type' => $alert->source_type,
            'identifier' => $identifier,
            'severity' => $alert->severity?->label() ?? __('Unknown'),
            'status' => $alert->status?->label() ?? __('Unknown'),
            'accounts' => count($alert->user_ids ?? []),
            'occurrences' => (int) $alert->occurrences,
            'last_seen' => optional($alert->last_seen_at)?->diffForHumans() ?? __('Unknown'),
        ];
    }

    protected function resetActionState(): void
    {
        $this->actionAlertId = null;
        $this->actionType = null;
        $this->notes = null;
        $this->actionContext = null;
    }

    protected function normalizeSeverity(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $allowed = array_map(
            static fn (MultiAccountAlertSeverity $severity): string => $severity->value,
            MultiAccountAlertSeverity::cases(),
        );

        return in_array($value, $allowed, true) ? $value : null;
    }

    protected function normalizeStatus(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $allowed = array_map(
            static fn (MultiAccountAlertStatus $status): string => $status->value,
            MultiAccountAlertStatus::cases(),
        );

        return in_array($value, $allowed, true) ? $value : null;
    }

    protected function normalizeSourceType(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $allowed = ['ip', 'device'];

        return in_array($value, $allowed, true) ? $value : null;
    }

    protected function normalizeString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    protected function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
