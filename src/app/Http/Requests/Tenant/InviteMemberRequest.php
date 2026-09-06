<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'string', 'in:admin,member'],
            'team_ids' => ['nullable', 'array'],
            'team_ids.*' => ['integer', 'exists:teams,id'],
            'initial_access' => ['nullable', 'array'],
            'initial_access.*.resource_type' => ['required_with:initial_access', 'string'],
            'initial_access.*.resource_id' => ['required_with:initial_access', 'integer'],
            'initial_access.*.permission' => ['required_with:initial_access', 'string', 'in:view,download,edit,share,manage'],
        ];
    }
}
