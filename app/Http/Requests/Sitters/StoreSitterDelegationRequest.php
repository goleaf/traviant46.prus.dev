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
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="StoreSitterDelegationRequest",
 *     type="object",
 *     required={"sitter_username"},
 *     @OA\Property(property="sitter_username", type="string", maxLength=255, example="example_sitter"),
 *     @OA\Property(
 *         property="permissions",
 *         type="array",
 *         @OA\Items(type="string", example="canFarm"),
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="expires_at",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         example="2025-01-01T12:00:00+00:00"
 *     )
 * )
 */
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
            'expires_at' => ['nullable', 'date_format:Y-m-d\TH:i:sP', 'after:now'],
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
