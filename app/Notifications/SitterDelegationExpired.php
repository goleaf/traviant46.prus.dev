<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SitterDelegation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SitterDelegationExpired extends Notification
{
    use Queueable;

    public function __construct(private readonly SitterDelegation $delegation)
    {
        $this->delegation->loadMissing('owner', 'sitter');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject('Sitter delegation expired');

        if ($notifiable instanceof User && $this->delegation->owner !== null && $notifiable->is($this->delegation->owner)) {
            $sitterName = $this->delegation->sitter?->username ?? __('your sitter');

            return $mail
                ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? $notifiable->username ?? '']))
                ->line(__('Your sitter :sitter no longer has access because the delegation expired.', ['sitter' => $sitterName]))
                ->line(__('You can assign a new sitter from your account settings if needed.'));
        }

        $ownerName = $this->delegation->owner?->username ?? __('the account owner');

        return $mail
            ->greeting(__('Hello :name,', ['name' => $notifiable instanceof User ? ($notifiable->name ?? $notifiable->username ?? '') : '']))
            ->line(__('Your delegation for :owner has expired and you no longer have sitter access.', ['owner' => $ownerName]))
            ->line(__('If the owner needs your help again they can set up a new delegation.'));
    }
}
