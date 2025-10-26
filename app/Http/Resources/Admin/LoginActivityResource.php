<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\LoginActivity */
class LoginActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->getKey(),
            'user' => [
                'id' => $this->user_id,
                'username' => $this->user?->username,
            ],
            'acting_sitter_id' => $this->acting_sitter_id,
            'via_sitter' => (bool) $this->via_sitter,
            'ip_address' => $this->ip_address,
            'device_hash' => $this->device_hash,
            'user_agent' => $this->user_agent,
            'logged_at' => optional($this->logged_at ?? $this->created_at)->toAtomString(),
        ];
    }
}
