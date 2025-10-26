<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\MultiAccountAlertSeverity;
use App\Enums\MultiAccountAlertStatus;
use App\Models\MultiAccountAlert;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterMultiAccountAlertsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', MultiAccountAlert::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $severityValues = array_map(static fn (MultiAccountAlertSeverity $severity): string => $severity->value, MultiAccountAlertSeverity::cases());
        $statusValues = array_map(static fn (MultiAccountAlertStatus $status): string => $status->value, MultiAccountAlertStatus::cases());

        return [
            'severity' => ['nullable', Rule::in($severityValues)],
            'status' => ['nullable', Rule::in($statusValues)],
            'source_type' => ['nullable', Rule::in(['ip', 'device'])],
            'ip' => ['nullable', 'string', 'max:45'],
            'device_hash' => ['nullable', 'string', 'max:64'],
            'search' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }
}
