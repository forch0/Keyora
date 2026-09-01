<?php

declare(strict_types=1);

namespace App\Http\Requests\Vault;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFolderRequest extends FormRequest
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

        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'parent_id' => ['sometimes', 'nullable', 'integer', Rule::exists('personal_vault_folders', 'id')->where('user_id', $userId)],
            'icon' => ['sometimes', 'nullable', 'string', 'max:50'],
            'color' => ['sometimes', 'nullable', 'string', 'max:20'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
