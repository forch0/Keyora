<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\SecureFile;
use App\Models\User;
use App\Services\AccessResolver;

class SecureFilePolicy
{
    public function __construct(
        private readonly AccessResolver $accessResolver,
    ) {}

    /**
     * View: uploader, team member, or has access grant.
     */
    public function view(User $user, SecureFile $file): bool
    {
        if ($this->accessResolver->can($user, Permission::View, $file)) {
            return true;
        }

        if ($file->user_id === $user->id) {
            return true;
        }

        $tenant = $file->tenant;
        if ($tenant !== null && $user->isAdminOf($tenant)) {
            return true;
        }

        if ($file->team_id !== null) {
            $team = $file->team;

            return $team !== null && $user->isTeamMember($team);
        }

        return false;
    }

    /**
     * Download: uploader, admin, team member, or has download grant.
     * Also requires download_enabled to be true.
     */
    public function download(User $user, SecureFile $file): bool
    {
        if (! $file->download_enabled) {
            return false;
        }

        if ($file->user_id === $user->id) {
            return true;
        }

        $tenant = $file->tenant;
        if ($tenant !== null && $user->isAdminOf($tenant)) {
            return true;
        }

        if ($file->team_id !== null) {
            $team = $file->team;
            if ($team !== null && $user->isTeamMember($team)) {
                return true;
            }
        }

        return $this->accessResolver->can($user, Permission::Download, $file);
    }

    /**
     * Update: uploader or has edit/manage permission.
     */
    public function update(User $user, SecureFile $file): bool
    {
        if ($this->accessResolver->can($user, Permission::Edit, $file)) {
            return true;
        }

        if ($file->user_id === $user->id) {
            return true;
        }

        $tenant = $file->tenant;

        return $tenant !== null && $user->isAdminOf($tenant);
    }

    /**
     * Delete: uploader or has manage permission.
     */
    public function delete(User $user, SecureFile $file): bool
    {
        if ($this->accessResolver->can($user, Permission::Manage, $file)) {
            return true;
        }

        if ($file->user_id === $user->id) {
            return true;
        }

        $tenant = $file->tenant;

        return $tenant !== null && $user->isAdminOf($tenant);
    }

    /**
     * Replace: uploader or has edit/manage permission.
     */
    public function replace(User $user, SecureFile $file): bool
    {
        return $this->update($user, $file);
    }
}
