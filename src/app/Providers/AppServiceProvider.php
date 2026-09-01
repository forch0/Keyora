<?php

namespace App\Providers;

use App\Models\Tenant;
use App\Models\User;
use App\Policies\TenantMemberPolicy;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
    }
}
