<?php

declare(strict_types=1);

namespace App\Http\Requests\Bulk;

use App\Enums\SubjectType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkShareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'max:100'],
            'ids.*' => ['integer'],
            'subject_type' => ['required', 'string', Rule::in(SubjectType::validClassStrings())],
            'subject_id' => ['required', 'integer'],
            'permission' => ['required', 'string', 'in:view,edit,manage'],
            'expires_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subject_type.in' => 'Subject type must be User, Team, or Tenant.',
        ];
    }
}
