<?php

declare(strict_types=1);

namespace App\Http\Requests\Sitters;

use App\Enums\SitterPermission;
use App\Models\SitterDelegation;
use App\Models\User;
use App\ValueObjects\SitterPermissionSet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSitterDelegationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $owner = $this->user();

        if (! $owner instanceof User) {
            return false;
        }

        return Gate::allows('create', [SitterDelegation::class, $owner]);
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $owner = $this->user();
        $ownerUsername = $owner instanceof User ? $owner->username : null;

        return [
            'sitter_username' => [
                'required',
                'string',
                'max:255',
                Rule::exists('users', 'username')->where(fn ($query) => $query->where('is_banned', false)),
                Rule::notIn($ownerUsername !== null ? [$ownerUsername] : []),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(SitterPermission::keys())],
            'expires_at' => ['nullable', 'date_format:c', 'after:now'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('permissions') && $this->input('permissions') === null) {
            $this->merge(['permissions' => []]);
        }
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        $owner = $this->user();

        return [
            function (Validator $validator) use ($owner): void {
                if (! $owner instanceof User) {
                    return;
                }

                $sitterUsername = $this->input('sitter_username');

                if (is_string($sitterUsername) && strcasecmp($owner->username, $sitterUsername) === 0) {
                    $validator->errors()->add('sitter_username', __('You cannot assign yourself as a sitter.'));
                }
            },
        ];
    }

    public function sitter(): User
    {
        /** @var User $sitter */
        $sitter = User::query()
            ->where('username', $this->validated('sitter_username'))
            ->firstOrFail();

        return $sitter;
    }

    public function permissionSet(): SitterPermissionSet
    {
        $permissions = $this->validated('permissions') ?? [];

        if (! is_array($permissions) || $permissions === []) {
            return SitterPermissionSet::none();
        }

        return SitterPermissionSet::fromArray($permissions);
    }

    public function expiresAt(): ?Carbon
    {
        $value = $this->validated('expires_at') ?? null;

        return $value === null ? null : Carbon::parse($value);
    }
}
