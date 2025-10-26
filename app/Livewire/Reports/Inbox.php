<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Livewire\Concerns\InteractsWithCommunicationPagination;
use App\Livewire\Concerns\InteractsWithCommunicationPermissions;
use App\Models\ReportRecipient;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

use function collect;

/**
 * Reimplements the legacy `BerichteCtrl` inbox with Livewire pagination, sitter-aware bulk actions,
 * and loss-percentage filters as outlined in {@see docs/communication-components.md Reports section}.
 */
class Inbox extends Component
{
    use InteractsWithCommunicationPagination;
    use InteractsWithCommunicationPermissions;
    use WithPagination;

    private const CATEGORY_OPTIONS = [
        'all' => 'All reports',
        'combat' => 'Combat',
        'trade' => 'Trade',
        'scouting' => 'Scouting',
        'system' => 'System',
    ];

    protected string $paginationTheme = 'tailwind';

    protected string $pageName = 'reports_page';

    #[Url]
    public string $category = 'all';

    #[Url(as: 'percent')]
    public ?int $lossThreshold = null;

    #[Url(as: 'o')]
    public bool $recursive = false;

    #[Url]
    public int $page = 1;

    /** @var array<int, int> */
    public array $selected = [];

    #[Computed]
    public function reports(): LengthAwarePaginator
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return ReportRecipient::query()->paginate(20);
        }

        $query = ReportRecipient::query()
            ->select('report_recipients.*')
            ->with('report')
            ->join('reports', 'reports.id', '=', 'report_recipients.report_id')
            ->where('report_recipients.recipient_id', $user->getKey())
            ->whereNull('report_recipients.deleted_at')
            ->orderByDesc('reports.triggered_at')
            ->orderByDesc('reports.id');

        if ($this->category !== 'all') {
            $query->where('reports.category', $this->category);
        }

        if ($this->lossThreshold !== null) {
            $query->where('reports.loss_percentage', '>=', $this->lossThreshold);
        }

        if (! $this->recursive) {
            $query->where(function (Builder $builder): void {
                $builder->whereNotIn('reports.delivery_scope', ['alliance'])
                    ->orWhereNull('reports.delivery_scope');
            });
        }

        return $this->paginateCommunication($query, 'reports', $this->page);
    }

    #[Computed]
    public function categories(): array
    {
        return self::CATEGORY_OPTIONS;
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatingLossThreshold(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatingPage(): void
    {
        $this->resetSelection();
    }

    public function toggleSelection(int $recipientId): void
    {
        if (in_array($recipientId, $this->selected, true)) {
            $this->selected = array_values(array_diff($this->selected, [$recipientId]));

            return;
        }

        $this->selected[] = $recipientId;
    }

    public function selectVisible(): void
    {
        $visible = collect($this->reports->items())
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        $this->selected = array_values(array_unique(array_merge($this->selected, $visible)));
    }

    public function resetSelection(): void
    {
        $this->selected = [];
    }

    public function bulkMarkAsRead(): void
    {
        $this->guardReportActions();

        $ids = $this->selectedRecipientIds();

        if ($ids === []) {
            return;
        }

        ReportRecipient::query()
            ->whereIn('id', $ids)
            ->update([
                'status' => 'read',
                'viewed_at' => now(),
            ]);

        $this->resetSelection();
        $this->dispatch('reports:refresh');
    }

    public function bulkArchive(): void
    {
        $this->guardReportActions();

        $ids = $this->selectedRecipientIds();

        if ($ids === []) {
            return;
        }

        ReportRecipient::query()
            ->whereIn('id', $ids)
            ->update([
                'archived_at' => now(),
            ]);

        $this->resetSelection();
        $this->dispatch('reports:refresh');
    }

    public function bulkDelete(): void
    {
        $this->guardReportActions();

        $ids = $this->selectedRecipientIds();

        if ($ids === []) {
            return;
        }

        ReportRecipient::query()
            ->whereIn('id', $ids)
            ->update([
                'deleted_at' => now(),
            ]);

        $this->resetSelection();
        $this->dispatch('reports:refresh');
    }

    public function render(): View
    {
        return view('livewire.reports.inbox', [
            'reports' => $this->reports,
            'categories' => $this->categories,
            'lossThreshold' => $this->lossThreshold,
            'capabilities' => [
                'bulk' => $this->communicationPermissions()->canArchive(),
            ],
        ]);
    }

    /**
     * @return list<int>
     */
    protected function selectedRecipientIds(): array
    {
        return $this->selectedRecipients()->pluck('id')->map(static fn (int $id): int => $id)->all();
    }

    protected function selectedRecipients()
    {
        if ($this->selected === []) {
            return collect();
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        return ReportRecipient::query()
            ->where('recipient_id', $user->getKey())
            ->whereIn('id', $this->selected)
            ->get();
    }

    protected function guardReportActions(): void
    {
        if (! $this->communicationPermissions()->canArchive()) {
            abort(403);
        }
    }
}
