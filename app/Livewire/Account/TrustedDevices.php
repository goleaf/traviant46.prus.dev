<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Models\TrustedDevice as TrustedDeviceModel;
use App\Models\User;
use App\Services\Security\TrustedDeviceManager;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

class TrustedDevices extends Component
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $devices = [];

    public ?string $currentDeviceId = null;

    public bool $managerEnabled = true;

    #[Validate('nullable|string|max:120')]
    public ?string $rememberLabel = null;

    /** @var array<string, string|null> */
    public array $renameLabels = [];

    public ?string $statusMessage = null;

    public function mount(TrustedDeviceManager $manager, Request $request): void
    {
        $user = $this->resolveUser();

        $this->managerEnabled = $manager->isEnabled();
        $this->refreshDevices($manager, $request, $user);
    }

    public function rememberDevice(TrustedDeviceManager $manager, Request $request): void
    {
        $user = $this->resolveUser();

        $this->validateOnly('rememberLabel');

        $device = $manager->rememberCurrentDevice($user, $request, $this->rememberLabel ?: null);

        $this->statusMessage = __('This device is now trusted. Future high-risk actions will skip additional prompts.');
        $this->rememberLabel = null;

        $this->refreshDevices($manager, $request, $user, $device->public_id);
    }

    public function revokeDevice(string $publicId, TrustedDeviceManager $manager, Request $request): void
    {
        $user = $this->resolveUser();

        if ($manager->revokeDevice($user, $publicId)) {
            $this->statusMessage = __('The selected device has been revoked and must re-verify before acting.');
        }

        $this->refreshDevices($manager, $request, $user);
    }

    public function revokeOthers(TrustedDeviceManager $manager, Request $request): void
    {
        $user = $this->resolveUser();

        $preserve = $this->currentDeviceId === null ? null : [$this->currentDeviceId];

        $manager->revokeAll($user, $preserve);

        $this->statusMessage = __('All other trusted devices have been revoked.');

        $this->refreshDevices($manager, $request, $user);
    }

    public function renameDevice(string $publicId, TrustedDeviceManager $manager, Request $request): void
    {
        $user = $this->resolveUser();

        $this->validate([
            "renameLabels.$publicId" => 'nullable|string|max:120',
        ]);

        $manager->renameDevice($user, $publicId, (string) ($this->renameLabels[$publicId] ?? ''));

        $this->statusMessage = __('Device label updated.');

        $this->refreshDevices($manager, $request, $user);
    }

    public function render()
    {
        return view('livewire.account.trusted-devices');
    }

    protected function refreshDevices(TrustedDeviceManager $manager, Request $request, User $user, ?string $focusId = null): void
    {
        $result = $manager->listDevices($user, $request);

        $timezone = $user->timezone ?? config('app.timezone');

        $current = $result['current'];
        $this->currentDeviceId = $current?->public_id;

        $this->devices = $result['devices']->map(function (TrustedDeviceModel $device) use ($timezone, $current, $focusId) {
            $lastUsed = $device->last_used_at ?? $device->first_trusted_at;

            return [
                'public_id' => $device->public_id,
                'label' => $device->label,
                'ip_address' => $device->ip_address,
                'user_agent' => $device->user_agent,
                'metadata' => Arr::only((array) $device->metadata, ['accept_language', 'sec_ch_ua', 'sec_ch_ua_platform']),
                'first_trusted_at' => optional($device->first_trusted_at)?->timezone($timezone)?->toDayDateTimeString(),
                'last_used_at' => optional($lastUsed)?->timezone($timezone)?->diffForHumans(null, Carbon::DIFF_ABSOLUTE, true, 2),
                'expires_at' => optional($device->expires_at)?->timezone($timezone)?->toDayDateTimeString(),
                'revoked_at' => optional($device->revoked_at)?->timezone($timezone)?->toDayDateTimeString(),
                'is_active' => $device->isActive(),
                'is_current' => $current !== null && $device->is($current),
                'highlight' => $focusId !== null && $device->public_id === $focusId,
            ];
        })->all();

        $this->renameLabels = collect($this->devices)
            ->mapWithKeys(fn ($device) => [$device['public_id'] => $device['label']])
            ->all();
    }

    protected function resolveUser(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
