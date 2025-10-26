<?php

declare(strict_types=1);

namespace App\Livewire\Messages;

use App\Livewire\Concerns\InteractsWithCommunicationPagination;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Sent extends Component
{
    use InteractsWithCommunicationPagination;
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    protected string $pageName = 'sent_messages_page';

    #[Url]
    public int $page = 1;

    #[Computed]
    public function messages(): LengthAwarePaginator
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return Message::query()->paginate(15);
        }

        $query = Message::query()
            ->select('messages.*')
            ->with([
                'recipients.recipient' => fn ($query) => $query->whereNull('message_recipients.deleted_at'),
            ])
            ->where('sender_id', $user->getKey())
            ->orderByDesc('sent_at');

        return $this->paginateCommunication($query, 'messages_sent', $this->page);
    }

    public function render(): View
    {
        return view('livewire.messages.sent', [
            'messages' => $this->messages,
        ]);
    }
}
