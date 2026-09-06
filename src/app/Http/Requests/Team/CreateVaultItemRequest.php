<?php

declare(strict_types=1);

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;

class CreateVaultItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:password,api_key,server,database,note'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:1000'],
            'url' => ['nullable', 'url', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'metadata' => ['nullable', 'array'],
            'metadata.host' => ['nullable', 'string', 'max:255'],
            'metadata.port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'metadata.provider' => ['nullable', 'string', 'max:100'],
            'metadata.db_type' => ['nullable', 'string', 'max:50'],
            'metadata.database_name' => ['nullable', 'string', 'max:255'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*.key' => ['required', 'string', 'max:100'],
            'custom_fields.*.value' => ['required', 'string', 'max:1000'],
            'folder_id' => ['nullable', 'integer', 'exists:vault_folders,id'],
        ];
    }
}
