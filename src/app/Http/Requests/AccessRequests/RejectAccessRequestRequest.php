<?php

declare(strict_types=1);

namespace App\Http\Requests\AccessRequests;

use Illuminate\Foundation\Http\FormRequest;

class RejectAccessRequestRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'review_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
