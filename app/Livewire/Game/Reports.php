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

    public function updatingKind(): void
    {
        $this->resetPage();
        $this->selectedReportId = null;
    }

    public function updatingPage(): void
    {
        $this->selectedReportId = null;
    }

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

    public function clearSelection(): void
    {
        $this->selectedReportId = null;
    }

    #[Computed]
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
    public function availableKinds(): array
    {
        return self::KIND_OPTIONS;
    }

    #[Computed]
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

    public function render(): View
    {
        return view('livewire.game.reports', [
            'reports' => $this->reports,
            'kinds' => $this->availableKinds,
            'selectedReport' => $this->selectedReport,
        ]);
    }
}
