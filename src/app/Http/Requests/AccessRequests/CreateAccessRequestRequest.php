<?php

declare(strict_types=1);

namespace App\Http\Requests\AccessRequests;

use App\Models\SecureFile;
use App\Models\SecureNote;
use App\Models\VaultItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class CreateAccessRequestRequest extends FormRequest
{
    /**
     * @return array<string, array<int, Rule|In|string>>
     */
    public function rules(): array
    {
        return [
            'resource_type' => ['required', 'string', Rule::in([VaultItem::class, SecureFile::class, SecureNote::class])],
            'resource_id' => ['required', 'integer'],
            'requested_permission' => ['required', 'string', Rule::in(['view', 'download', 'edit', 'share', 'manage'])],
            'requested_duration' => ['nullable', 'string', Rule::in(['15m', '30m', '1h', '24h', '7d', '30d', 'permanent'])],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
