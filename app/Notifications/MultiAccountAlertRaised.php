<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\MultiAccountAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MultiAccountAlertRaised extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected MultiAccountAlert $alert,
    ) {}

    public function via(object $notifiable): array
    {
        return config('multiaccount.notifications.channels', ['mail']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $alert = $this->alert;

        $timeline = collect($alert->metadata['recent_timeline'] ?? [])
            ->map(static fn (array $item): string => sprintf(
                '#%s — User %s at %s (%s)',
                $item['login_activity_id'] ?? '?',
                $item['user_id'] ?? '?',
                $item['logged_at'] ?? 'unknown time',
                $item['ip_address'] ?? 'n/a'
            ))
            ->take(5)
            ->implode("\n");

        $message = (new MailMessage())
            ->subject(sprintf('[%s] Multi-account alert detected', strtoupper((string) $alert->severity?->value)))
            ->line('A high severity multi-account alert has been raised.')
            ->line(sprintf('Alert ID: %s', $alert->alert_id))
            ->line(sprintf('Source: %s (%s)', $alert->source_type, $alert->source_type === 'device' ? ($alert->device_hash ?? 'n/a') : ($alert->ip_address ?? 'n/a')))
            ->line(sprintf('Involved accounts: %s', implode(', ', $alert->user_ids ?? [])))
            ->line(sprintf('Occurrences in window: %d', (int) $alert->occurrences))
            ->line(sprintf('Last seen: %s', optional($alert->last_seen_at)->toDayDateTimeString() ?? 'Unknown'))
            ->line('Recent timeline:')
            ->line($timeline !== '' ? $timeline : 'No timeline entries captured.')
            ->action('Review alerts', route('admin.multi-account-alerts.index'));

        if (($alert->metadata['vpn_suspected'] ?? false) === true) {
            $message->line('VPN heuristics indicated this connection may be a VPN exit node.');
        }

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'alert_id' => $this->alert->alert_id,
            'severity' => $this->alert->severity?->value,
            'status' => $this->alert->status?->value,
            'user_ids' => $this->alert->user_ids,
        ];
    }
}
