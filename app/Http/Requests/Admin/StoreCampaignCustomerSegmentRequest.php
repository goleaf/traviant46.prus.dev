<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Filament\Resources\CampaignCustomerSegmentResource;
use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignCustomerSegmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return CampaignCustomerSegmentResource::rules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('Please enter a segment name.'),
            'name.string' => __('The segment name must be a valid string.'),
            'name.max' => __('The segment name may not be greater than 255 characters.'),
            'slug.string' => __('The slug must be a valid string.'),
            'slug.max' => __('The slug may not be greater than 255 characters.'),
            'slug.unique' => __('Another segment is already using this slug.'),
            'description.string' => __('The description must be a valid string.'),
            'is_active.boolean' => __('The active flag must be true or false.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('segment name'),
            'slug' => __('slug'),
            'description' => __('description'),
            'is_active' => __('active status'),
        ];
    }
}
