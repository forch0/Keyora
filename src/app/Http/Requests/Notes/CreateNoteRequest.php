<?php

declare(strict_types=1);

namespace App\Http\Requests\Notes;

use Illuminate\Foundation\Http\FormRequest;

class CreateNoteRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:50000'],
            'content_format' => ['nullable', 'string', 'in:markdown,html'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'folder_id' => ['nullable', 'integer', 'exists:note_folders,id'],
            'is_pinned' => ['nullable', 'boolean'],
        ];
    }
}
