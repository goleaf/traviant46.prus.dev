<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class PasswordResetLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $field = Fortify::email();

        return [
            $field => ['required', 'email'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $field = Fortify::email();

        return [
            "{$field}.required" => __('Please enter the email address associated with your account.'),
            "{$field}.email" => __('Enter a valid email address.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $field = Fortify::email();

        return [
            $field => __('email address'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $field = Fortify::email();

        if (config('fortify.lowercase_usernames') && $this->filled($field)) {
            $this->merge([
                $field => Str::lower((string) $this->input($field)),
            ]);
        }
    }
}
