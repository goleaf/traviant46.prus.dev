<?php

namespace App\Http\Requests;

use App\Services\Game\BuildingQueueService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QueueBuildingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $village = $this->route('village');

        return $village && $this->user()?->can('manageQueue', $village);
    }

    public function rules(): array
    {
        $service = app(BuildingQueueService::class);

        return [
            'building_key' => ['required', 'string', Rule::in($service->availableBuildingTypes())],
            'target_level' => ['required', 'integer', 'min:1', 'max:100'],
            'slot' => ['required', 'integer', 'between:1,40'],
            'construction_time' => ['nullable', 'integer', 'min:1'],
            'cost' => ['nullable', 'array'],
            'cost.wood' => ['nullable', 'integer', 'min:0'],
            'cost.clay' => ['nullable', 'integer', 'min:0'],
            'cost.iron' => ['nullable', 'integer', 'min:0'],
            'cost.crop' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
