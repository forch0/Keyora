<?php

declare(strict_types=1);

namespace App\Http\Requests\SecureLinks;

use Illuminate\Foundation\Http\FormRequest;

class VerifyLinkRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'password' => ['nullable', 'string'],
            'otp_code' => ['nullable', 'string'],
        ];
    }
}
