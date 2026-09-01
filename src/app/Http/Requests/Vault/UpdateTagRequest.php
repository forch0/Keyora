<?php

declare(strict_types=1);

namespace App\Http\Requests\Vault;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;
        $tagId = $this->route('tag');

        return [
            'name' => ['sometimes', 'string', 'max:50', Rule::unique('personal_vault_tags', 'name')->where('user_id', $userId)->ignore($tagId instanceof \App\Models\PersonalVaultTag ? $tagId->id : 0)],
            'color' => ['sometimes', 'nullable', 'string', 'max:20'],
        ];
    }
}
