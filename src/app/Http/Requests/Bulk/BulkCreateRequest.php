<?php

declare(strict_types=1);

namespace App\Http\Requests\Bulk;

use Illuminate\Foundation\Http\FormRequest;

class BulkCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'max:50'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.type' => ['required', 'string', 'in:password,api_key,server,database'],
            'items.*.username' => ['nullable', 'string', 'max:255'],
            'items.*.password' => ['required', 'string'],
            'items.*.url' => ['nullable', 'string', 'max:2048'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
