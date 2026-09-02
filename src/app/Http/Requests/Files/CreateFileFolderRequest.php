<?php

declare(strict_types=1);

namespace App\Http\Requests\Files;

use Illuminate\Foundation\Http\FormRequest;

class CreateFileFolderRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'parent_id' => ['nullable', 'integer', 'exists:file_folders,id'],
        ];
    }
}
