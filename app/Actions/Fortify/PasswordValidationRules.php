<?php

namespace App\Actions\Fortify;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        $config = config('security.passwords');

        $rule = Password::min((int) ($config['min_length'] ?? 12));

        if (($config['require_letters'] ?? true) === true) {
            $rule->letters();
        }

        if (($config['require_mixed_case'] ?? true) === true) {
            $rule->mixedCase();
        }

        if (($config['require_numbers'] ?? true) === true) {
            $rule->numbers();
        }

        if (($config['require_symbols'] ?? true) === true) {
            $rule->symbols();
        }

        $uncompromised = Arr::get($config, 'uncompromised', []);

        if (($uncompromised['enabled'] ?? true) === true) {
            $rule->uncompromised((int) ($uncompromised['threshold'] ?? 3));
        }

        return ['required', 'string', $rule, 'confirmed'];
    }
}
