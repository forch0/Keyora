<?php

declare(strict_types=1);

namespace App\Http\Requests\Notes;

use Illuminate\Foundation\Http\FormRequest;

class CreateNoteFolderRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'parent_id' => ['nullable', 'integer', 'exists:note_folders,id'],
        ];
    }
}
