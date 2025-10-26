<?php

declare(strict_types=1);

namespace App\Livewire\Messages;

use App\Livewire\Concerns\InteractsWithCommunicationPermissions;
use App\Livewire\Concerns\UsesSpamHeuristics;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\User;
use App\Services\Communication\Exceptions\SpamViolationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

use function collect;

/**
 * Handles message composition, validation, and sitter-aware throttling rules as documented in
 * {@see docs/communication-components.md Compose section}, including spam heuristics and reply pre-fills.
 */
class Compose extends Component
{
    use InteractsWithCommunicationPermissions;
    use UsesSpamHeuristics;

    #[Validate('required|string|max:50')]
    public string $recipient = '';

    #[Validate('required|string|max:120')]
    public string $subject = '';

    #[Validate('required|string|min:5|max:4000')]
    public string $body = '';

    /** @var array<int, array{id: int, username: string, name: string|null}> */
    public array $addressBook = [];

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public ?int $replyingTo = null;

    public function mount(?MessageRecipient $reply = null): void
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $this->addressBook = $this->loadAddressBook($user);
        }

        if ($reply instanceof MessageRecipient && $reply->recipient_id === $user?->getKey()) {
            $source = $reply->message;
            $sender = $source?->sender;

            if ($sender instanceof User) {
                $this->recipient = $sender->username;
            }

            if ($source !== null) {
                $this->subject = $this->formatReplySubject((string) $source->subject);
                $this->body = $this->quoteMessage((string) $source->body);
            }

            $this->replyingTo = $reply->getKey();
        }
    }

    public function send(): void
    {
        $this->guardMessageActions();

        $validated = $this->validate();

        $sender = Auth::user();

        if (! $sender instanceof User) {
            abort(403);
        }

        $recipient = User::query()
            ->whereRaw('LOWER(username) = ?', [strtolower($validated['recipient'])])
            ->first();

        if (! $recipient instanceof User) {
            $this->addError('recipient', __('The selected recipient could not be found.'));

            return;
        }

        try {
            $checksum = $this->spamHeuristics()->guardSending(
                $sender,
                $recipient,
                $validated['subject'],
                $validated['body'],
            );
        } catch (SpamViolationException $exception) {
            $this->errorMessage = $exception->getMessage();
            $this->statusMessage = null;

            return;
        }

        DB::transaction(function () use ($validated, $sender, $recipient, $checksum): void {
            $message = Message::query()->create([
                'sender_id' => $sender->getKey(),
                'subject' => $validated['subject'],
                'body' => $validated['body'],
                'checksum' => $checksum,
                'delivery_scope' => 'individual',
                'sent_at' => now(),
                'metadata' => [
                    'replying_to' => $this->replyingTo,
                ],
            ]);

            $message->recipients()->create([
                'recipient_id' => $recipient->getKey(),
                'status' => 'unread',
                'is_archived' => false,
            ]);
        });

        $this->addressBook = $this->loadAddressBook($sender);

        $this->statusMessage = __('Message delivered to :username.', ['username' => $recipient->username]);
        $this->errorMessage = null;

        $this->reset(['subject', 'body', 'replyingTo']);

        $this->dispatch('message-sent', status: $this->statusMessage);
    }

    public function fillRecipient(string $username): void
    {
        $this->recipient = trim($username);
    }

    public function render(): View
    {
        return view('livewire.messages.compose', [
            'addressBook' => $this->addressBook,
            'statusMessage' => $this->statusMessage,
            'errorMessage' => $this->errorMessage,
        ]);
    }

    protected function guardMessageActions(): void
    {
        if (! $this->communicationPermissions()->canPerformBulkMessageActions()) {
            abort(403);
        }
    }

    /**
     * @return array<int, array{id: int, username: string, name: string|null}>
     */
    protected function loadAddressBook(User $user): array
    {
        $recentRecipientIds = Message::query()
            ->select('message_recipients.recipient_id')
            ->join('message_recipients', 'messages.id', '=', 'message_recipients.message_id')
            ->where('messages.sender_id', $user->getKey())
            ->orderByDesc('messages.sent_at')
            ->limit(12)
            ->pluck('message_recipients.recipient_id')
            ->filter()
            ->unique()
            ->values();

        if ($recentRecipientIds->isEmpty()) {
            return [];
        }

        return User::query()
            ->whereIn('id', $recentRecipientIds)
            ->orderBy('username')
            ->get(['id', 'username', 'name'])
            ->map(fn (User $account) => [
                'id' => $account->getKey(),
                'username' => $account->username,
                'name' => $account->name,
            ])
            ->all();
    }

    protected function formatReplySubject(string $subject): string
    {
        $normalized = trim($subject);

        return Str::startsWith(Str::lower($normalized), 're:') ? $normalized : 'RE: '.$normalized;
    }

    protected function quoteMessage(string $body): string
    {
        $lines = collect(preg_split("/(\r\n|\n|\r)/", trim($body)) ?: []);

        $quoted = $lines->map(static fn (string $line): string => '> '.trim($line))->implode(PHP_EOL);

        return PHP_EOL.PHP_EOL.$quoted;
    }
}
