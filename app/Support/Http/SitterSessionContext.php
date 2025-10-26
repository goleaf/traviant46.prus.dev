<?php

declare(strict_types=1);

namespace App\Support\Http;

use App\Enums\SitterPermission;
use App\Models\SitterDelegation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use function collect;

class SitterSessionContext
{
    public function __construct(private readonly Request $request) {}

    /**
     * @return array{
     *     active: bool,
     *     account?: array{id:int,username:string|null,name:string|null},
     *     sitter?: array{id:int,username?:string|null,name?:string|null},
     *     delegation?: array{
     *         present: bool,
     *         permissions: array<int, array{key:string,label:string}>,
     *         bitmask: int,
     *         expires_at?: ?string,
     *         expires_human?: ?string
     *     }
     * }
     */
    public function resolve(): array
    {
        $session = $this->request->session();

        if (! $session->get('auth.acting_as_sitter', false)) {
            return ['active' => false];
        }

        $account = $this->request->user();

        if (! $account instanceof User) {
            return ['active' => false];
        }

        $sitterId = (int) $session->get('auth.sitter_id');

        if ($sitterId <= 0) {
            return ['active' => false];
        }

        $sitter = User::query()->find($sitterId);

        $delegation = SitterDelegation::query()
            ->forAccount($account)
            ->forSitter($sitterId)
            ->first();

        $permissions = $delegation instanceof SitterDelegation
            ? $this->mapPermissions($delegation->permissionKeys())
            : [];
        $preset = $delegation?->preset();
        $expiresAtIso = optional($delegation?->expires_at)->toIso8601String();
        $expiresHuman = optional($delegation?->expires_at)?->diffForHumans(now(), [
            'parts' => 2,
            'short' => true,
        ]);

        return [
            'active' => true,
            'account' => [
                'id' => $account->getKey(),
                'username' => $account->username,
                'name' => $account->name,
            ],
            'sitter' => [
                'id' => $sitter?->getKey() ?? $sitterId,
                'username' => $sitter?->username,
                'name' => $sitter?->name,
            ],
            'delegation' => [
                'present' => $delegation !== null,
                'permissions' => $permissions,
                'bitmask' => $delegation?->permissionBitmask() ?? 0,
                'preset' => $preset?->value,
                'preset_label' => $preset?->label(),
                'expires_at' => $expiresAtIso,
                'expires_human' => $expiresHuman,
            ],
        ];
    }

    /**
     * @param array<int, string> $keys
     * @return array<int, array{key:string,label:string}>
     */
    private function mapPermissions(array $keys): array
    {
        return collect($keys)
            ->map(function (string $key): array {
                $permission = SitterPermission::fromKey($key);
                $label = $permission?->label() ?? Str::headline(str_replace('_', ' ', $key));

                return [
                    'key' => $key,
                    'label' => $label,
                ];
            })
            ->values()
            ->all();
    }
}
