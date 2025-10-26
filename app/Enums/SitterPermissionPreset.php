<?php

declare(strict_types=1);

namespace App\Enums;

use function collect;

/**
 * Represents curated combinations of sitter permissions aligned with TravianT roles.
 *
 * @method array<int, SitterPermission> permissions()
 */
enum SitterPermissionPreset: string
{
    case Observer = 'observer';
    case Guardian = 'guardian';
    case Raider = 'raider';
    case Quartermaster = 'quartermaster';
    case Diplomat = 'diplomat';
    case Steward = 'steward';
    case FullAccess = 'full_access';

    /**
     * @return array<int, SitterPermission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Observer => [],
            self::Guardian => [
                SitterPermission::Reinforce,
                SitterPermission::SendResources,
                SitterPermission::ManageMessages,
            ],
            self::Raider => [
                SitterPermission::Raid,
                SitterPermission::Reinforce,
                SitterPermission::SendResources,
            ],
            self::Quartermaster => [
                SitterPermission::SendResources,
                SitterPermission::ManageArchives,
            ],
            self::Diplomat => [
                SitterPermission::ManageMessages,
                SitterPermission::ManageArchives,
            ],
            self::Steward => [
                SitterPermission::ManageMessages,
                SitterPermission::ManageArchives,
                SitterPermission::AllianceContribute,
            ],
            self::FullAccess => SitterPermission::cases(),
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Observer => __('Observer (read-only)'),
            self::Guardian => __('Guardian (defensive support)'),
            self::Raider => __('Raider (offensive support)'),
            self::Quartermaster => __('Quartermaster (logistics)'),
            self::Diplomat => __('Diplomat (communications)'),
            self::Steward => __('Steward (alliance liaison)'),
            self::FullAccess => __('Full access (owner equivalent)'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Observer => __('No actionable rights; sitter can monitor account activity.'),
            self::Guardian => __('Focus on defense, resource balancing, and replying to urgent messages.'),
            self::Raider => __('Grant offensive troop control alongside provisioning capabilities.'),
            self::Quartermaster => __('Coordinate resource shipments and keep reports organised.'),
            self::Diplomat => __('Handle diplomacy, messaging, and inbox curation.'),
            self::Steward => __('Represent the account in alliance matters while managing communications.'),
            self::FullAccess => __('Mirror owner privileges, including gold spending and alliance contributions.'),
        };
    }

    /**
     * @return array<int, string>
     */
    public function permissionValues(): array
    {
        return array_map(
            static fn (SitterPermission $permission): string => $permission->value,
            $this->permissions(),
        );
    }

    public static function detectFromPermissions(?array $permissions): ?self
    {
        $normalized = collect($permissions ?? SitterPermission::cases())
            ->map(static fn ($permission) => $permission instanceof SitterPermission ? $permission->value : (string) $permission)
            ->sort()
            ->values()
            ->all();

        foreach (self::cases() as $preset) {
            $presetValues = $preset === self::FullAccess
                ? collect(SitterPermission::cases())->map->value->sort()->values()->all()
                : collect($preset->permissionValues())->sort()->values()->all();

            if ($normalized === $presetValues) {
                return $preset;
            }
        }

        return null;
    }
}
