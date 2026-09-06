<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\SecureNote;
use App\Models\User;
use App\Services\TenantManager;

class CreateNoteAction
{
    public function __construct(
        private readonly TenantManager $tenantManager,
    ) {}

    /**
     * Create a secure note (personal, team, or org-wide).
     *
     * @param  array<string, mixed>  $data  title, content, content_format, team_id, folder_id, is_pinned
     */
    public function __invoke(User $creator, array $data): SecureNote
    {
        $tenantId = $data['tenant_id'] ?? null;
        $teamId = $data['team_id'] ?? null;

        // If team_id is set, this is a team note — inherit tenant from team
        if ($teamId !== null && $tenantId === null) {
            $tenantId = $this->tenantManager->currentTenantId();
        }

        // If no team_id and no tenant_id, this is a personal note (tenant_id = null)
        return SecureNote::create([
            'tenant_id' => $tenantId,
            'team_id' => $teamId,
            'user_id' => $creator->id,
            'folder_id' => $data['folder_id'] ?? null,
            'title' => $data['title'],
            'content' => $data['content'],
            'content_format' => $data['content_format'] ?? 'markdown',
            'is_pinned' => $data['is_pinned'] ?? false,
        ]);
    }
}
