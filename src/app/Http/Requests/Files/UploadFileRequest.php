<?php

declare(strict_types=1);

namespace App\Http\Requests\Files;

use Illuminate\Foundation\Http\FormRequest;

class UploadFileRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'folder_id' => ['nullable', 'integer', 'exists:file_folders,id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
