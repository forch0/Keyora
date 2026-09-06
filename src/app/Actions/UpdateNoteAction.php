<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\SecureNote;

class UpdateNoteAction
{
    /**
     * Update a secure note's metadata and content.
     *
     * @param  array<string, mixed>  $data  title, content, content_format, folder_id, is_pinned
     */
    public function __invoke(SecureNote $note, array $data): SecureNote
    {
        $updateData = [];

        if (isset($data['title'])) {
            $updateData['title'] = $data['title'];
        }

        if (isset($data['content'])) {
            $updateData['content'] = $data['content'];
        }

        if (isset($data['content_format'])) {
            $updateData['content_format'] = $data['content_format'];
        }

        if (array_key_exists('folder_id', $data)) {
            $updateData['folder_id'] = $data['folder_id'];
        }

        if (isset($data['is_pinned'])) {
            $updateData['is_pinned'] = $data['is_pinned'];
        }

        $note->update($updateData);

        return $note->refresh();
    }
}
