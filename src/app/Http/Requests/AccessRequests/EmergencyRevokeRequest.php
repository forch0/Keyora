<?php

declare(strict_types=1);

namespace App\Http\Requests\AccessRequests;

use Illuminate\Foundation\Http\FormRequest;

class EmergencyRevokeRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
