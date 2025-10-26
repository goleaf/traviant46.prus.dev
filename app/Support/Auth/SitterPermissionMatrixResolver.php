<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Enums\SitterPermission;
use App\Models\User;
use App\ValueObjects\SitterRestriction;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;

final class SitterPermissionMatrixResolver
{
    /**
     * @var array<string, array{permission: string, default_reason: string}>
     */
    private const DEFAULT_MATRIX = [
        'build' => [
            'permission' => 'build',
            'default_reason' => 'Construction actions are disabled while you are acting as a sitter for this account.',
        ],
        'train' => [
            'permission' => 'send_troops',
            'default_reason' => 'Troop training is reserved for the account owner.',
        ],
        'send' => [
            'permission' => 'send_troops',
            'default_reason' => 'Dispatching troops is restricted while you are a sitter.',
        ],
    ];

    private const DEFAULT_NO_DELEGATION_REASON = 'Your sitter permissions are no longer active for this account.';

    /**
     * @var array<string, array{permission: string, reason?: string|null}>
     */
    private array $matrix;

    public function __construct(private readonly User $owner)
    {
        $stored = is_array($owner->sitter_permission_matrix)
            ? $owner->sitter_permission_matrix
            : [];

        $this->matrix = $this->prepareMatrix($stored);
    }

    public function restriction(string $action): SitterRestriction
    {
        $entry = $this->resolveEntry($action);
        $permission = $this->resolvePermission($action, $entry);

        if (! SitterContext::isActingAsSitter()) {
            return new SitterRestriction($action, true, null, $permission);
        }

        $delegation = SitterContext::activeDelegation($this->owner);

        if ($delegation === null) {
            return new SitterRestriction(
                $action,
                false,
                __(self::DEFAULT_NO_DELEGATION_REASON),
                $permission,
            );
        }

        $permitted = $delegation->allows($permission);
        $reason = $permitted ? null : __($entry['reason'] ?? $this->defaultReason($action));

        return new SitterRestriction($action, $permitted, $reason, $permission);
    }

    /**
     * @return array<string, SitterRestriction>
     */
    public function restrictions(): array
    {
        $actions = array_keys(self::DEFAULT_MATRIX);

        $restrictions = [];

        foreach ($actions as $action) {
            $restrictions[$action] = $this->restriction($action);
        }

        return $restrictions;
    }

    /**
     * @throws AuthorizationException
     */
    public function assertAllowed(string $action): void
    {
        $restriction = $this->restriction($action);

        if (! $restriction->isPermitted()) {
            throw new AuthorizationException($restriction->reason ?? __('This action is not available.'));
        }
    }

    /**
     * @param array<string, mixed> $stored
     * @return array<string, array{permission: string, reason?: string|null}>
     */
    private function prepareMatrix(array $stored): array
    {
        $matrix = [];

        foreach (self::DEFAULT_MATRIX as $action => $defaults) {
            $entry = is_array($stored[$action] ?? null) ? $stored[$action] : [];

            $permission = is_string($entry['permission'] ?? null)
                ? $entry['permission']
                : $defaults['permission'];

            $reason = array_key_exists('reason', $entry) ? $entry['reason'] : null;
            $reason = is_string($reason) ? trim($reason) : null;

            $matrix[$action] = [
                'permission' => $permission,
            ];

            if ($reason !== null && $reason !== '') {
                $matrix[$action]['reason'] = $reason;
            }
        }

        return $matrix;
    }

    /**
     * @param array{permission: string, reason?: string|null} $entry
     */
    private function resolvePermission(string $action, array $entry): SitterPermission
    {
        $permission = SitterPermission::fromKey($entry['permission']);

        if ($permission === null) {
            $fallback = self::DEFAULT_MATRIX[$action]['permission'] ?? null;

            if (! is_string($fallback)) {
                throw new InvalidArgumentException(sprintf('Unknown sitter permission mapping for action "%s".', $action));
            }

            $permission = SitterPermission::fromKey($fallback);
        }

        if ($permission === null) {
            throw new InvalidArgumentException(sprintf('Unable to resolve sitter permission for action "%s".', $action));
        }

        return $permission;
    }

    /**
     * @return array{permission: string, reason?: string|null}
     */
    private function resolveEntry(string $action): array
    {
        if (! isset($this->matrix[$action])) {
            throw new InvalidArgumentException(sprintf('Unknown sitter action "%s".', $action));
        }

        return $this->matrix[$action];
    }

    private function defaultReason(string $action): string
    {
        $defaults = self::DEFAULT_MATRIX[$action] ?? null;

        if (! is_array($defaults) || ! isset($defaults['default_reason'])) {
            return __('This action is restricted for sitters.');
        }

        return __($defaults['default_reason']);
    }
}
