<?php

declare(strict_types=1);

namespace App\Livewire\Messages;

use App\Livewire\Concerns\InteractsWithCommunicationPagination;
use App\Livewire\Concerns\InteractsWithCommunicationPermissions;
use App\Livewire\Concerns\UsesSpamHeuristics;
use App\Models\MessageRecipient;
use App\Models\User;
use App\Services\Communication\Exceptions\SpamViolationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

use function collect;

/**
 * Modernises the legacy `NachrichtenCtrl` inbox by migrating its pagination, sitter-aware bulk actions,
 * and recursive village toggle into Livewire while preserving the behavioural notes captured in
 * {@see docs/communication-components.md Inbox section}.
 */
class Inbox extends Component
{
    use InteractsWithCommunicationPagination;
    use InteractsWithCommunicationPermissions;
    use UsesSpamHeuristics;
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    protected string $pageName = 'messages_page';

    #[Url(as: 'o')]
    public bool $recursive = false;

    #[Url]
    public int $page = 1;

    /** @var array<int, int> */
    public array $selected = [];

    public ?string $spamWarning = null;

    public function mount(): void
    {
        $user = Auth::user();

        if ($user instanceof User) {
            try {
                $this->spamHeuristics()->guardUnreadFlood($user);
            } catch (SpamViolationException $exception) {
                $this->spamWarning = $exception->getMessage();
            }
        }
    }

    #[Computed]
    public function messages(): LengthAwarePaginator
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return MessageRecipient::query()->paginate(20);
        }

        $query = MessageRecipient::query()
            ->select('message_recipients.*')
            ->with(['message.sender'])
            ->join('messages', 'messages.id', '=', 'message_recipients.message_id')
            ->where('message_recipients.recipient_id', $user->getKey())
            ->whereNull('message_recipients.deleted_at')
            ->orderByDesc('messages.sent_at');

        if (! $this->recursive) {
            $query->where(function (Builder $builder): void {
                $builder->whereNotIn('messages.delivery_scope', ['alliance'])
                    ->orWhereNull('messages.delivery_scope');
            });
        }

        return $this->paginateCommunication($query, 'messages', $this->page);
    }

    #[Computed]
    public function capabilities(): array
    {
        $permissions = $this->communicationPermissions();

        return [
            'bulk' => $permissions->canPerformBulkMessageActions(),
            'archive' => $permissions->canArchive(),
        ];
    }

    public function updatingPage(): void
    {
        $this->resetSelection();
    }

    public function updatingRecursive(): void
    {
        $this->resetPage();
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
        $visibleIds = collect($this->messages->items())
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        $this->selected = array_values(array_unique(array_merge($this->selected, $visibleIds)));
    }

    public function resetSelection(): void
    {
        $this->selected = [];
    }

    public function toggleRead(int $recipientId): void
    {
        $this->guardMessageActions();

        $recipient = $this->recipientForUser($recipientId);

        if (! $recipient instanceof MessageRecipient) {
            return;
        }

        if ($recipient->read_at === null) {
            $recipient->forceFill([
                'status' => 'read',
                'read_at' => now(),
            ])->save();
        } else {
            $recipient->forceFill([
                'status' => 'unread',
                'read_at' => null,
            ])->save();
        }

        $this->dispatch('messages:refresh');
    }

    public function bulkMarkAsRead(): void
    {
        $this->guardMessageActions();

        $ids = $this->selectedRecipientIds();

        if ($ids === []) {
            return;
        }

        MessageRecipient::query()
            ->whereIn('id', $ids)
            ->update([
                'status' => 'read',
                'read_at' => now(),
            ]);

        $this->resetSelection();
        $this->dispatch('messages:refresh');
    }

    public function bulkMarkAsUnread(): void
    {
        $this->guardMessageActions();

        $ids = $this->selectedRecipientIds();

        if ($ids === []) {
            return;
        }

        MessageRecipient::query()
            ->whereIn('id', $ids)
            ->update([
                'status' => 'unread',
                'read_at' => null,
            ]);

        $this->resetSelection();
        $this->dispatch('messages:refresh');
    }

    public function bulkArchive(): void
    {
        $this->guardArchiveActions();

        $ids = $this->selectedRecipientIds();

        if ($ids === []) {
            return;
        }

        MessageRecipient::query()
            ->whereIn('id', $ids)
            ->update([
                'is_archived' => true,
                'archived_at' => now(),
            ]);

        $this->resetSelection();
        $this->dispatch('messages:refresh');
    }

    public function bulkDelete(): void
    {
        $this->guardArchiveActions();

        $ids = $this->selectedRecipientIds();

        if ($ids === []) {
            return;
        }

        MessageRecipient::query()
            ->whereIn('id', $ids)
            ->update([
                'deleted_at' => now(),
            ]);

        $this->resetSelection();
        $this->dispatch('messages:refresh');
    }

    public function render(): View
    {
        return view('livewire.messages.inbox', [
            'messages' => $this->messages,
            'capabilities' => $this->capabilities,
            'spamWarning' => $this->spamWarning,
        ]);
    }

    /**
     * @return list<int>
     */
    protected function selectedRecipientIds(): array
    {
        return $this->selectedRecipients()->pluck('id')->map(static fn (int $id): int => $id)->all();
    }

    protected function selectedRecipients(): Collection
    {
        if ($this->selected === []) {
            return collect();
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        return MessageRecipient::query()
            ->where('recipient_id', $user->getKey())
            ->whereIn('id', $this->selected)
            ->get();
    }

    protected function recipientForUser(int $recipientId): ?MessageRecipient
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        return MessageRecipient::query()
            ->where('recipient_id', $user->getKey())
            ->whereKey($recipientId)
            ->first();
    }

    protected function guardMessageActions(): void
    {
        if (! $this->communicationPermissions()->canPerformBulkMessageActions()) {
            abort(403);
        }
    }

    protected function guardArchiveActions(): void
    {
        if (! $this->communicationPermissions()->canArchive()) {
            abort(403);
        }
    }
}
