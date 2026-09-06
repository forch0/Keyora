<?php

namespace App\Providers;

use App\Models\PersonalVaultItem;
use App\Models\SecureFile;
use App\Models\SecureNote;
use App\Models\SecurityAlert;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use App\Observers\DashboardCacheObserver;
use App\Policies\TenantMemberPolicy;
use App\Policies\TenantPolicy;
use App\Services\AccessResolver;
use App\Services\TenantManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantManager::class);

        // AccessResolver is scoped (per-request) because it depends on
        // the current tenant context via TenantManager.
        $this->app->scoped(AccessResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Dashboard cache invalidation observers (Module 31)
        $observer = DashboardCacheObserver::class;
        PersonalVaultItem::observe($observer);
        SecureFile::observe($observer);
        SecureNote::observe($observer);
        Team::observe($observer);
        SecurityAlert::observe($observer);

        // TenantMemberPolicy gates — these operate on a Tenant (and optionally
        // a target User) but are distinct from TenantPolicy. Registered as
        // gates rather than a model policy because the same Tenant model
        // already has TenantPolicy for CRUD operations.
        $policy = $this->app->make(TenantMemberPolicy::class);

        Gate::define('member.view', fn (User $user, Tenant $tenant) => $policy->view($user, $tenant));
        Gate::define('member.invite', fn (User $user, Tenant $tenant) => $policy->invite($user, $tenant));
        Gate::define('member.update', fn (User $user, Tenant $tenant, User $member) => $policy->update($user, $tenant, $member));
        Gate::define('member.suspend', fn (User $user, Tenant $tenant, User $member) => $policy->suspend($user, $tenant, $member));
        Gate::define('member.restore', fn (User $user, Tenant $tenant, User $member) => $policy->restore($user, $tenant, $member));
        Gate::define('member.remove', fn (User $user, Tenant $tenant, User $member) => $policy->remove($user, $tenant, $member));
        Gate::define('member.manageInvitations', fn (User $user, Tenant $tenant) => $policy->manageInvitations($user, $tenant));

        // TenantPolicy gate — activity feed access (Module 20)
        $tenantPolicy = $this->app->make(TenantPolicy::class);
        Gate::define('viewActivityFeed', fn (User $user, Tenant $tenant) => $tenantPolicy->viewActivityFeed($user, $tenant));

        // API docs access (Module 28 — Scramble). In local environment, access
        // is always allowed by RestrictedDocsAccess middleware. In other
        // environments, only users with the viewApiDocs gate can access docs.
        Gate::define('viewApiDocs', fn (?User $user) => app()->environment('local', 'testing')
            || in_array($user?->email, config('scramble.allowed_emails', []), true));
    }
}
