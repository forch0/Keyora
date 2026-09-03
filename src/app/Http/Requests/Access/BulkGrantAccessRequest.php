<?php

declare(strict_types=1);

namespace App\Http\Requests\Access;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkGrantAccessRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'team_ids' => ['required', 'array', 'min:1'],
            'team_ids.*' => ['required', 'integer', 'exists:'.Team::class.',id'],
            'permission' => ['required', 'string', 'in:view,download,edit,share,manage'],
        ];
    }
}
