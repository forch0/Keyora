<?php

declare(strict_types=1);

namespace App\Http\Requests\Tools;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class GeneratePasswordRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'length' => ['nullable', 'integer', 'min:8', 'max:128'],
            'uppercase' => ['nullable', 'boolean'],
            'lowercase' => ['nullable', 'boolean'],
            'numbers' => ['nullable', 'boolean'],
            'symbols' => ['nullable', 'boolean'],
            'exclude_similar' => ['nullable', 'boolean'],
            'exclude_ambiguous' => ['nullable', 'boolean'],
            'min_uppercase' => ['nullable', 'integer', 'min:0'],
            'min_lowercase' => ['nullable', 'integer', 'min:0'],
            'min_numbers' => ['nullable', 'integer', 'min:0'],
            'min_symbols' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Validate that at least one character type is enabled.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $uppercase = (bool) $this->input('uppercase', true);
            $lowercase = (bool) $this->input('lowercase', true);
            $numbers = (bool) $this->input('numbers', true);
            $symbols = (bool) $this->input('symbols', true);

            if (! $uppercase && ! $lowercase && ! $numbers && ! $symbols) {
                $validator->errors()->add('uppercase', 'At least one character type must be enabled.');
            }
        });
    }
}
