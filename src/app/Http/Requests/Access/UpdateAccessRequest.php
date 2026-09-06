<?php

declare(strict_types=1);

namespace App\Http\Requests\Access;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class UpdateAccessRequest extends FormRequest
{
    /**
     * @return array<string, list<string|Rule|In>>
     */
    public function rules(): array
    {
        return [
            'permission' => ['required', 'string', Rule::in(['view', 'download', 'edit', 'share', 'manage'])],
            'expires_at' => ['nullable', 'date'],
            'max_views' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
