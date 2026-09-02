<?php

declare(strict_types=1);

namespace App\Http\Requests\SecureLinks;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CreateSecureLinkRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'recipient_email' => ['nullable', 'email'],
            'password' => ['nullable', 'string', 'min:4', 'max:100'],
            'require_otp' => ['nullable', 'boolean'],
            'require_email_verification' => ['nullable', 'boolean'],
            'permission' => ['required', 'string', 'in:view,download'],
            'download_enabled' => ['nullable', 'boolean'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'first_view_expires_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            'max_views' => ['nullable', 'integer', 'min:1'],
            'is_one_time' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            // is_one_time and max_views are mutually exclusive
            if ($this->boolean('is_one_time') && $this->filled('max_views')) {
                $validator->errors()->add('is_one_time', 'Cannot use is_one_time with max_views.');
            }

            // require_otp requires recipient_email
            if ($this->boolean('require_otp') && ! $this->filled('recipient_email')) {
                $validator->errors()->add('require_otp', 'OTP requires a recipient email.');
            }
        });
    }
}
