<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\SitterDelegation
 */
class SitterAssignmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'sitter' => [
                'id' => $this->sitter->getKey(),
                'username' => $this->sitter->username,
                'name' => $this->sitter->name,
            ],
            'permissions' => $this->permissions,
            'expires_at' => optional($this->expires_at)->toIso8601String(),
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
