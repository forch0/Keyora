<?php

declare(strict_types=1);

namespace App\Http\Requests\Files;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFileRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'folder_id' => ['nullable', 'integer', 'exists:file_folders,id'],
            'download_enabled' => ['sometimes', 'boolean'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
