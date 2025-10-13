<?php

namespace App\Services\Auth;

use App\Enums\SitterPermission;
use App\Models\Game\Village;
use App\Models\MultiAccountAlert;
use App\Models\User;
use Illuminate\Contracts\Session\Session as SessionStore;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class SessionContextManager
{
    private const ROOT = 'travian.context';
    private const SITTER = self::ROOT.'.sitter';
    private const VILLAGE = self::ROOT.'.village';
    private const OVERRIDE = self::ROOT.'.override';
    private const SECURITY = self::ROOT.'.security';

    public function __construct(private SessionStore $session)
    {
    }

    public function enterSitterContext(User $account, User $sitter, array $context = []): void
    {
        $permissions = $this->normalisePermissions($context['permissions'] ?? []);

        $this->session->put(self::SITTER, array_filter([
            'active' => true,
            'account_id' => $account->getKey(),
            'sitter_id' => $sitter->getKey(),
            'permissions' => $permissions,
            'source' => $context['assignment_source'] ?? 'legacy',
            'expires_at' => $this->normaliseDate($context['assignment_expires_at'] ?? null),
        ], static fn ($value) => $value !== null));
    }

    public function clearSitterContext(): void
    {
        $this->session->forget(self::SITTER);
    }

    public function actingAsSitter(): bool
    {
        return (bool) data_get($this->sitterContext(), 'active', false);
    }

    public function sitterId(): ?int
    {
        $id = data_get($this->sitterContext(), 'sitter_id');

        return $id === null ? null : (int) $id;
    }

    public function controllingAccountId(): ?int
    {
        $id = data_get($this->sitterContext(), 'account_id');

        return $id === null ? null : (int) $id;
    }

    public function sitterPermissions(): array
    {
        return data_get($this->sitterContext(), 'permissions', []);
    }

    public function sitterHasPermission(string $permission): bool
    {
        $permissions = $this->sitterPermissions();

        if (in_array(SitterPermission::MANAGE_VILLAGE, $permissions, true)) {
            if (in_array($permission, [SitterPermission::VIEW_VILLAGE, SitterPermission::SWITCH_VILLAGE], true)) {
                return true;
            }
        }

        return in_array($permission, $permissions, true);
    }

    public function setActiveVillage(?Village $village, ?int $kid = null): void
    {
        if (! $village instanceof Village) {
            $this->clearActiveVillage();

            return;
        }

        $this->session->put(self::VILLAGE, array_filter([
            'id' => $village->getKey(),
            'kid' => $kid ?? $this->resolveVillageKid($village),
            'name' => $village->getAttribute('name'),
            'owner_id' => $this->resolveVillageOwnerId($village),
        ], static fn ($value) => $value !== null));
    }

    public function clearActiveVillage(): void
    {
        $this->session->forget(self::VILLAGE);
    }

    public function activeVillageId(): ?int
    {
        $id = data_get($this->villageContext(), 'id');

        return $id === null ? null : (int) $id;
    }

    public function activeVillageKid(): ?int
    {
        $kid = data_get($this->villageContext(), 'kid');

        return $kid === null ? null : (int) $kid;
    }

    public function enterAdminOverride(User $admin, User $target): void
    {
        $this->session->put(self::OVERRIDE, [
            'active' => true,
            'admin_id' => $admin->getKey(),
            'target_user_id' => $target->getKey(),
            'started_at' => Carbon::now()->toIso8601String(),
        ]);
    }

    public function clearAdminOverride(): void
    {
        $this->session->forget(self::OVERRIDE);
    }

    public function adminOverrideActive(): bool
    {
        return (bool) data_get($this->adminOverrideContext(), 'active', false);
    }

    public function overrideTargetUserId(): ?int
    {
        $id = data_get($this->adminOverrideContext(), 'target_user_id');

        return $id === null ? null : (int) $id;
    }

    public function overrideAdminId(): ?int
    {
        $id = data_get($this->adminOverrideContext(), 'admin_id');

        return $id === null ? null : (int) $id;
    }

    public function flagMultiAccountAlert(MultiAccountAlert $alert): void
    {
        $this->session->put(self::SECURITY.'.multi_account', array_filter([
            'alert_id' => $alert->getKey(),
            'conflict_user_id' => $alert->conflict_user_id,
            'ip_address' => $alert->ip_address,
            'occurrences' => $alert->occurrences,
            'last_seen_at' => $this->normaliseDate($alert->last_seen_at),
        ], static fn ($value) => $value !== null));
    }

    public function multiAccountAlertContext(): array
    {
        return (array) $this->session->get(self::SECURITY.'.multi_account', []);
    }

    public function allowsVillageAction(User $actor, Village $village, string $permission): bool
    {
        $ownerId = $this->resolveVillageOwnerId($village);

        if ($ownerId !== null && $actor->getKey() === $ownerId) {
            return true;
        }

        if ($this->adminOverrideActive()
            && $this->overrideAdminId() === $actor->getKey()
            && $this->overrideTargetUserId() === $ownerId) {
            return true;
        }

        if (! $this->actingAsSitter()) {
            return false;
        }

        if ($this->sitterId() !== $actor->getKey()) {
            return false;
        }

        if ($this->controllingAccountId() !== $ownerId) {
            return false;
        }

        if ($this->sitterHasPermission($permission)) {
            return true;
        }

        if ($permission === SitterPermission::VIEW_VILLAGE) {
            return $this->sitterHasPermission(SitterPermission::SWITCH_VILLAGE);
        }

        return false;
    }

    public function sitterContext(): array
    {
        return (array) $this->session->get(self::SITTER, []);
    }

    public function villageContext(): array
    {
        return (array) $this->session->get(self::VILLAGE, []);
    }

    public function adminOverrideContext(): array
    {
        return (array) $this->session->get(self::OVERRIDE, []);
    }

    public function toArray(): array
    {
        return [
            'sitter' => $this->sitterContext(),
            'village' => $this->villageContext(),
            'override' => $this->adminOverrideContext(),
            'security' => (array) $this->session->get(self::SECURITY, []),
        ];
    }

    public function flush(): void
    {
        $this->session->forget(self::ROOT);
    }

    private function normalisePermissions(array $permissions): array
    {
        $valid = array_intersect(array_map('strval', $permissions), SitterPermission::all());

        if (! in_array(SitterPermission::VIEW_VILLAGE, $valid, true)) {
            $valid[] = SitterPermission::VIEW_VILLAGE;
        }

        return array_values(array_unique($valid));
    }

    private function normaliseDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->toIso8601String();
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value)->toIso8601String();
        }

        return null;
    }

    private function resolveVillageOwnerId(Village $village): ?int
    {
        $ownerId = $village->getAttribute('owner_id') ?? $village->getAttribute('user_id');

        if ($ownerId !== null) {
            return (int) $ownerId;
        }

        $owner = $village->getAttribute('owner');

        if ($owner instanceof User) {
            return $owner->getKey();
        }

        if (method_exists($village, 'owner')) {
            $owner = $village->owner()->first();

            return $owner instanceof User ? $owner->getKey() : null;
        }

        return null;
    }

    private function resolveVillageKid(Village $village): ?int
    {
        $kid = Arr::first([
            $village->getAttribute('kid'),
            $village->getAttribute('map_id'),
            $village->getAttribute('tile_id'),
        ], static fn ($value) => $value !== null);

        return is_numeric($kid) ? (int) $kid : null;
    }
}
