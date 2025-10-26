<?php

declare(strict_types=1);

namespace App\Services\Communication\Exceptions;

use RuntimeException;

final class SpamViolationException extends RuntimeException
{
    public static function messageRateLimited(int $windowMinutes, int $limit): self
    {
        return new self(
            __('Spam protection: You have reached the limit of :limit messages every :minutes minutes. Please wait before sending again.', [
                'limit' => $limit,
                'minutes' => $windowMinutes,
            ]),
        );
    }

    public static function duplicateContent(): self
    {
        return new self(__('Spam protection: You have recently sent this message. Try rephrasing it before resending.'));
    }

    public static function unreadFlood(int $limit): self
    {
        return new self(__('Spam protection: The recipient has more than :limit unread messages from you.', [
            'limit' => $limit,
        ]));
    }
}
