<?php

declare(strict_types=1);

namespace App\Livewire\Game;

use App\Models\Game\Report;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Reports extends Component
{
    use WithPagination;

    /**
     * Available report kinds surfaced to the filter select.
     *
     * @var array<string, string>
     */
    private const KIND_OPTIONS = [
        'all' => 'All kinds',
        'combat' => 'Combat',
        'scout' => 'Scout',
        'trade' => 'Trade',
        'system' => 'System',
    ];

    protected string $paginationTheme = 'tailwind';

    protected string $pageName = 'game_reports_page';

    #[Url]
    public string $kind = 'all';

    #[Url]
    public int $page = 1;

    public ?int $selectedReportId = null;

    /**
     * Reset pagination and the detail panel whenever the filter changes.
     */
    public function updatingKind(): void
    {
        $this->resetPage();
        $this->selectedReportId = null;
    }

    /**
     * Reset the detail panel when the player switches pages.
     */
    public function updatingPage(): void
    {
        $this->selectedReportId = null;
    }

    /**
     * Load the requested report into the detail panel when it belongs to the user.
     */
    public function selectReport(int $reportId): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $exists = Report::query()
            ->whereKey($reportId)
            ->where('for_user_id', $user->getKey())
            ->exists();

        if (! $exists) {
            return;
        }

        $this->selectedReportId = $reportId;
    }

    /**
     * Clear the report selection to collapse the detail panel.
     */
    public function clearSelection(): void
    {
        $this->selectedReportId = null;
    }

    #[Computed]
    /**
     * Fetch the paginated list of reports for the authenticated user.
     */
    public function reports(): LengthAwarePaginator
    {
        $user = Auth::user();

        $query = Report::query()
            ->orderByDesc('created_at');

        if ($user instanceof User) {
            $query->where('for_user_id', $user->getKey());
        } else {
            $query->whereRaw('0 = 1');
        }

        if ($this->kind !== 'all') {
            $query->where('kind', $this->kind);
        }

        return $query->paginate(10, ['*'], $this->pageName);
    }

    #[Computed]
    /**
     * Expose filter options for the reports list.
     *
     * @return array<string, string>
     */
    public function availableKinds(): array
    {
        return self::KIND_OPTIONS;
    }

    #[Computed]
    /**
     * Resolve the currently selected report for the detail panel.
     */
    public function selectedReport(): ?Report
    {
        if ($this->selectedReportId === null) {
            return null;
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        return Report::query()
            ->whereKey($this->selectedReportId)
            ->where('for_user_id', $user->getKey())
            ->first();
    }

    /**
     * Render the reports inbox surface.
     */
    public function render(): View
    {
        return view('livewire.game.reports', [
            'reports' => $this->reports,
            'kinds' => $this->availableKinds,
            'selectedReport' => $this->selectedReport,
        ]);
    }
}
