<?php

declare(strict_types=1);

namespace App\Http\Requests\Bulk;

use Illuminate\Foundation\Http\FormRequest;

class BulkArchiveRequest extends FormRequest
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
        ];
    }
}
