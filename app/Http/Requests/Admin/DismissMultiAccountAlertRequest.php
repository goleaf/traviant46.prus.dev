<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\MultiAccountAlert;
use Illuminate\Foundation\Http\FormRequest;

class DismissMultiAccountAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var MultiAccountAlert $alert */
        $alert = $this->route('multi_account_alert');

        return $this->user()?->can('dismiss', $alert) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
