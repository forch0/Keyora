<?php

declare(strict_types=1);

namespace App\Http\Requests\Notes;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNoteRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string', 'max:50000'],
            'content_format' => ['nullable', 'string', 'in:markdown,html'],
            'folder_id' => ['nullable', 'integer', 'exists:note_folders,id'],
            'is_pinned' => ['sometimes', 'boolean'],
        ];
    }
}
