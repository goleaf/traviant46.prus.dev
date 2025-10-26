<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\User;
use App\Services\Communication\Exceptions\SpamViolationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

final class SpamHeuristicsService
{
    private readonly int $rateLimitWindowMinutes;

    private readonly int $rateLimitThreshold;

    private readonly int $duplicateWindowMinutes;

    private readonly int $recipientUnreadThreshold;

    private readonly int $globalUnreadThreshold;

    public function __construct()
    {
        $config = (array) config('game.communication.spam', []);

        $this->rateLimitWindowMinutes = max(1, (int) ($config['rate_limit_window_minutes'] ?? 10));
        $this->rateLimitThreshold = max(1, (int) ($config['rate_limit_threshold'] ?? 12));
        $this->duplicateWindowMinutes = max(1, (int) ($config['duplicate_window_minutes'] ?? 15));
        $this->recipientUnreadThreshold = max(1, (int) ($config['recipient_unread_threshold'] ?? 3));
        $this->globalUnreadThreshold = max(
            $this->recipientUnreadThreshold + 1,
            (int) ($config['global_unread_threshold'] ?? 10),
        );
    }

    public function guardSending(User $sender, User $recipient, string $subject, string $body): string
    {
        $checksum = md5(mb_strtolower($subject).'|'.trim($body));

        $this->ensureWithinRateLimit($sender);
        $this->ensureNoDuplicate($sender, $recipient, $checksum);
        $this->ensureRecipientUnreadWithinLimit($sender, $recipient);

        return $checksum;
    }

    public function guardUnreadFlood(User $sender): void
    {
        $threshold = $this->globalUnreadThreshold;

        $totalUnread = MessageRecipient::query()
            ->whereNull('deleted_at')
            ->whereNull('read_at')
            ->whereHas('message', static function (Builder $builder) use ($sender): void {
                $builder->where('sender_id', $sender->getKey());
            })
            ->count();

        if ($totalUnread >= $threshold) {
            throw SpamViolationException::messageRateLimited($this->rateLimitWindowMinutes, $this->rateLimitThreshold);
        }
    }

    private function ensureWithinRateLimit(User $sender): void
    {
        $windowStart = Carbon::now()->subMinutes($this->rateLimitWindowMinutes);

        $recentMessages = Message::query()
            ->where('sender_id', $sender->getKey())
            ->whereNotNull('sent_at')
            ->where('sent_at', '>=', $windowStart)
            ->count();

        if ($recentMessages >= $this->rateLimitThreshold) {
            throw SpamViolationException::messageRateLimited($this->rateLimitWindowMinutes, $this->rateLimitThreshold);
        }
    }

    private function ensureNoDuplicate(User $sender, User $recipient, string $checksum): void
    {
        $duplicateExists = Message::query()
            ->where('sender_id', $sender->getKey())
            ->where('checksum', $checksum)
            ->whereNotNull('sent_at')
            ->where('sent_at', '>=', Carbon::now()->subMinutes($this->duplicateWindowMinutes))
            ->whereHas('recipients', static function (Builder $builder) use ($recipient): void {
                $builder->where('recipient_id', $recipient->getKey());
            })
            ->exists();

        if ($duplicateExists) {
            throw SpamViolationException::duplicateContent();
        }
    }

    private function ensureRecipientUnreadWithinLimit(User $sender, User $recipient): void
    {
        $unreadCount = MessageRecipient::query()
            ->where('recipient_id', $recipient->getKey())
            ->whereNull('deleted_at')
            ->whereNull('read_at')
            ->whereHas('message', static function (Builder $builder) use ($sender): void {
                $builder->where('sender_id', $sender->getKey());
            })
            ->count();

        if ($unreadCount >= $this->recipientUnreadThreshold) {
            throw SpamViolationException::unreadFlood($this->recipientUnreadThreshold);
        }
    }
}
