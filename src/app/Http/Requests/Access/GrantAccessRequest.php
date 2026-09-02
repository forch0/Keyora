<?php

declare(strict_types=1);

namespace App\Http\Requests\Access;

use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class GrantAccessRequest extends FormRequest
{
    /**
     * @return array<string, list<string|Rule|In>>
     */
    public function rules(): array
    {
        return [
            'subject_type' => ['required', 'string', Rule::in([User::class, Team::class, Tenant::class])],
            'subject_id' => ['required', 'integer'],
            'permission' => ['required', 'string', Rule::in(['view', 'download', 'edit', 'share', 'manage'])],
            'duration' => ['nullable', 'string', Rule::in(['15m', '30m', '1h', '24h'])],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'max_views' => ['nullable', 'integer', 'min:1'],
            'start_on_first_view' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subject_type.in' => 'Subject type must be User, Team, or Tenant.',
            'permission.in' => 'Permission must be one of: view, download, edit, share, manage.',
        ];
    }

    /**
     * Validate that the subject belongs to the current tenant.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $subjectType = $this->input('subject_type');
            $subjectId = (int) $this->input('subject_id');
            $tenantId = app(TenantManager::class)->currentTenantId();

            // duration and expires_at are mutually exclusive
            if ($this->filled('duration') && $this->filled('expires_at')) {
                $validator->errors()->add('duration', 'Provide either duration or expires_at, not both.');
            }

            // start_on_first_view with starts_at is invalid
            if ($this->boolean('start_on_first_view') && $this->filled('starts_at')) {
                $validator->errors()->add('start_on_first_view', 'Cannot use start_on_first_view with starts_at.');
            }

            if ($subjectType === User::class) {
                $user = User::find($subjectId);
                if ($user === null) {
                    $validator->errors()->add('subject_id', 'User not found.');

                    return;
                }
                if ($tenantId !== null && ! $user->tenants()->where('tenants.id', $tenantId)->exists()) {
                    $validator->errors()->add('subject_id', 'User is not a member of this tenant.');
                }
            } elseif ($subjectType === Team::class) {
                $team = Team::find($subjectId);
                if ($team === null) {
                    $validator->errors()->add('subject_id', 'Team not found.');

                    return;
                }
                if ($tenantId !== null && $team->tenant_id !== $tenantId) {
                    $validator->errors()->add('subject_id', 'Team does not belong to this tenant.');
                }
            } elseif ($subjectType === Tenant::class) {
                if ($tenantId !== null && $subjectId !== $tenantId) {
                    $validator->errors()->add('subject_id', 'Can only grant tenant-wide access to the current tenant.');
                }
            }
        });
    }
}
