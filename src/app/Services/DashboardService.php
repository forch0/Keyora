<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AccessGrant;
use App\Models\AccessRequest;
use App\Models\ActivityLog;
use App\Models\PersonalVaultItem;
use App\Models\SecureFile;
use App\Models\SecureNote;
use App\Models\SecurityAlert;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VaultItem;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates data for dashboard endpoints.
 *
 * All methods use efficient aggregate queries (COUNT, GROUP BY) to avoid N+1 problems.
 */
class DashboardService
{
    /**
     * Build the personal dashboard for a user.
     *
     * @return array<string, mixed>
     */
    public function personalDashboard(User $user): array
    {
        return [
            'vault_summary' => $this->personalVaultSummary($user),
            'recently_viewed' => $this->recentlyViewedItems($user),
            'recently_added' => $this->recentlyAddedItems($user),
            'shared_with_me' => $this->sharedWithMe($user),
            'expiring_access' => $this->expiringAccess($user),
            'pending_requests' => $this->pendingRequests($user),
            'security_alerts_unread' => $this->unreadSecurityAlerts($user),
        ];
    }

    /**
     * Build the company dashboard for a tenant.
     *
     * @return array<string, mixed>
     */
    public function companyDashboard(Tenant $tenant): array
    {
        return [
            'overview' => $this->companyOverview($tenant),
            'members' => $this->recentMembers($tenant),
            'teams' => $this->teamsWithCounts($tenant),
            'access_requests' => $this->accessRequestSummary($tenant),
            'temporary_access' => $this->temporaryAccessSummary($tenant),
            'security_activity' => $this->securityActivitySummary($tenant),
            'expiring_access' => $this->companyExpiringAccess($tenant),
            'recent_activity' => $this->recentActivity($tenant),
        ];
    }

    /**
     * Build the usage dashboard for a tenant.
     *
     * @return array<string, mixed>
     */
    public function usageDashboard(Tenant $tenant): array
    {
        $limits = $this->planLimits($tenant);
        $usage = $this->usageStats($tenant);

        return [
            'plan' => $tenant->plan,
            'limits' => $limits,
            'usage' => $usage,
            'percentages' => $this->calculatePercentages($limits, $usage),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function personalVaultSummary(User $user): array
    {
        $baseQuery = PersonalVaultItem::where('user_id', $user->id);

        $totalItems = (clone $baseQuery)->whereNull('archived_at')->count();
        $favoritesCount = (clone $baseQuery)->where('favorite', true)->whereNull('archived_at')->count();
        $archivedCount = (clone $baseQuery)->whereNotNull('archived_at')->count();

        $byType = (clone $baseQuery)
            ->whereNull('archived_at')
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return [
            'total_items' => $totalItems,
            'by_type' => $byType,
            'favorites_count' => $favoritesCount,
            'archived_count' => $archivedCount,
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function recentlyViewedItems(User $user): array
    {
        return PersonalVaultItem::where('user_id', $user->id)
            ->whereNull('archived_at')
            ->whereNotNull('last_accessed_at')
            ->orderByDesc('last_accessed_at')
            ->limit(5)
            ->get(['id', 'name', 'type', 'last_accessed_at'])
            ->toArray();
    }

    /**
     * @return array<int, mixed>
     */
    private function recentlyAddedItems(User $user): array
    {
        return PersonalVaultItem::where('user_id', $user->id)
            ->whereNull('archived_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'name', 'type', 'created_at'])
            ->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function sharedWithMe(User $user): array
    {
        $total = AccessGrant::withoutTenant()
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->whereNull('revoked_at')
            ->count();

        $recentGrantIds = AccessGrant::withoutTenant()
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->whereNull('revoked_at')
            ->orderByDesc('granted_at')
            ->limit(5)
            ->pluck('grantable_id', 'grantable_type')
            ->toArray();

        return [
            'total' => $total,
            'items' => $recentGrantIds,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function expiringAccess(User $user): array
    {
        $threshold = now()->addHours(48);

        $query = AccessGrant::withoutTenant()
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->whereNull('revoked_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $threshold)
            ->where('expires_at', '>', now());

        $count = $query->count();
        $items = (clone $query)
            ->orderBy('expires_at')
            ->limit(10)
            ->get(['id', 'grantable_type', 'grantable_id', 'permission', 'expires_at'])
            ->toArray();

        return [
            'count' => $count,
            'items' => $items,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function pendingRequests(User $user): array
    {
        $sent = AccessRequest::where('requester_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $received = AccessRequest::where('resource_owner_id', $user->id)
            ->where('status', 'pending')
            ->count();

        return [
            'sent' => $sent,
            'received' => $received,
        ];
    }

    private function unreadSecurityAlerts(User $user): int
    {
        return SecurityAlert::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * @return array<string, int>
     */
    private function companyOverview(Tenant $tenant): array
    {
        $totalMembers = $tenant->users()->count();
        $activeMembers = $tenant->users()->wherePivot('status', 'active')->count();
        $suspendedMembers = $tenant->users()->wherePivot('status', 'suspended')->count();
        $totalTeams = Team::where('tenant_id', $tenant->id)->count();
        $totalVaultItems = VaultItem::where('tenant_id', $tenant->id)->count();
        $totalFiles = SecureFile::where('tenant_id', $tenant->id)->count();
        $totalNotes = SecureNote::where('tenant_id', $tenant->id)->count();

        return [
            'total_members' => $totalMembers,
            'active_members' => $activeMembers,
            'suspended_members' => $suspendedMembers,
            'total_teams' => $totalTeams,
            'total_vault_items' => $totalVaultItems,
            'total_files' => $totalFiles,
            'total_notes' => $totalNotes,
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function recentMembers(Tenant $tenant): array
    {
        return $tenant->users()
            ->orderByDesc('tenant_user.joined_at')
            ->limit(10)
            ->get(['users.id', 'users.name', 'users.email', 'tenant_user.role', 'tenant_user.joined_at'])
            ->toArray();
    }

    /**
     * @return array<int, mixed>
     */
    private function teamsWithCounts(Tenant $tenant): array
    {
        return Team::where('tenant_id', $tenant->id)
            ->withCount(['vaultItems', 'files', 'notes'])
            ->get(['id', 'name'])
            ->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function accessRequestSummary(Tenant $tenant): array
    {
        $pending = AccessRequest::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->count();

        $recent = AccessRequest::where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'requester_id', 'resource_type', 'requested_permission', 'status', 'created_at'])
            ->toArray();

        return [
            'pending' => $pending,
            'recent' => $recent,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function temporaryAccessSummary(Tenant $tenant): array
    {
        $active = AccessGrant::where('tenant_id', $tenant->id)
            ->whereNull('revoked_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->count();

        $expiring24h = AccessGrant::where('tenant_id', $tenant->id)
            ->whereNull('revoked_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDay())
            ->count();

        return [
            'active' => $active,
            'expiring_24h' => $expiring24h,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function securityActivitySummary(Tenant $tenant): array
    {
        $recentAlerts = SecurityAlert::where('tenant_id', $tenant->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $recentRevocations = ActivityLog::where('tenant_id', $tenant->id)
            ->where('action', 'like', '%revok%')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $failedLogins24h = ActivityLog::where('action', 'auth.login_failed')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return [
            'recent_alerts' => $recentAlerts,
            'recent_revocations' => $recentRevocations,
            'failed_logins_24h' => $failedLogins24h,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function companyExpiringAccess(Tenant $tenant): array
    {
        $threshold = now()->addHours(48);

        $query = AccessGrant::where('tenant_id', $tenant->id)
            ->whereNull('revoked_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $threshold)
            ->where('expires_at', '>', now());

        $count = $query->count();
        $items = (clone $query)
            ->orderBy('expires_at')
            ->limit(10)
            ->get(['id', 'subject_type', 'subject_id', 'grantable_type', 'grantable_id', 'permission', 'expires_at'])
            ->toArray();

        return [
            'count' => $count,
            'items' => $items,
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function recentActivity(Tenant $tenant): array
    {
        return ActivityLog::where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'user_id', 'action', 'subject_type', 'subject_id', 'created_at'])
            ->toArray();
    }

    /**
     * @return array<string, int>
     */
    private function planLimits(Tenant $tenant): array
    {
        $limits = config("plans.{$tenant->plan}", config('plans.free', []));

        return [
            'max_members' => $limits['max_members'] ?? 5,
            'max_storage_mb' => $limits['max_storage_mb'] ?? 100,
            'max_vault_items' => $limits['max_vault_items'] ?? 100,
        ];
    }

    /**
     * @return array<string, int|float>
     */
    private function usageStats(Tenant $tenant): array
    {
        $members = $tenant->users()->count();
        $vaultItems = VaultItem::where('tenant_id', $tenant->id)->count();
        $files = SecureFile::where('tenant_id', $tenant->id)->count();
        $notes = SecureNote::where('tenant_id', $tenant->id)->count();

        $storageBytes = SecureFile::where('tenant_id', $tenant->id)->sum('size');
        $storageMb = round($storageBytes / (1024 * 1024), 2);

        return [
            'members' => $members,
            'storage_used_mb' => $storageMb,
            'vault_items' => $vaultItems,
            'files' => $files,
            'notes' => $notes,
        ];
    }

    /**
     * @param  array<string, int>  $limits
     * @param  array<string, int|float>  $usage
     * @return array<string, int>
     */
    private function calculatePercentages(array $limits, array $usage): array
    {
        $membersPercent = $limits['max_members'] > 0
            ? (int) round(((int) $usage['members'] / $limits['max_members']) * 100)
            : 0;

        $storagePercent = $limits['max_storage_mb'] > 0
            ? (int) round(((float) $usage['storage_used_mb'] / $limits['max_storage_mb']) * 100)
            : 0;

        $vaultItemsPercent = $limits['max_vault_items'] > 0
            ? (int) round(((int) $usage['vault_items'] / $limits['max_vault_items']) * 100)
            : 0;

        return [
            'members' => min(100, $membersPercent),
            'storage' => min(100, $storagePercent),
            'vault_items' => min(100, $vaultItemsPercent),
        ];
    }
}
