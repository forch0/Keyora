<?php

declare(strict_types=1);

namespace App\Http\Requests\Vault;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTagRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:50', Rule::unique('personal_vault_tags', 'name')->where('user_id', $userId)],
            'color' => ['nullable', 'string', 'max:20'],
        ];
    }
}
