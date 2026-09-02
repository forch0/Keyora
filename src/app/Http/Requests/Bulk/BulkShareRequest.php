<?php

declare(strict_types=1);

namespace App\Http\Requests\Bulk;

use Illuminate\Foundation\Http\FormRequest;

class BulkShareRequest extends FormRequest
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
            'ids' => ['required', 'array', 'max:100'],
            'ids.*' => ['integer'],
            'subject_type' => ['required', 'string'],
            'subject_id' => ['required', 'integer'],
            'permission' => ['required', 'string', 'in:view,edit,manage'],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}
