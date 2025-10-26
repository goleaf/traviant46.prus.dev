<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\MultiAccountAlert;
use Illuminate\Foundation\Http\FormRequest;

class IpLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', MultiAccountAlert::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ip' => ['required', 'ip'],
        ];
    }
}
