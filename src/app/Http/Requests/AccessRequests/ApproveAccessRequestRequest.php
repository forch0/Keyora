<?php

declare(strict_types=1);

namespace App\Http\Requests\AccessRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class ApproveAccessRequestRequest extends FormRequest
{
    /**
     * @return array<string, array<int, Rule|In|string>>
     */
    public function rules(): array
    {
        return [
            'granted_permission' => ['nullable', 'string', Rule::in(['view', 'download', 'edit', 'share', 'manage'])],
            'granted_duration' => ['nullable', 'string', Rule::in(['15m', '30m', '1h', '24h', '7d', '30d', 'permanent'])],
            'review_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
