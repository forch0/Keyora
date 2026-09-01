<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;

class CreateTenantAction
{
    /**
     * Create a tenant and attach the user as owner.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(User $owner, array $attributes): Tenant
    {
        $name = $attributes['name'];
        $slug = $attributes['slug'] ?? $this->generateUniqueSlug($name);

        return tap(Tenant::create([
            'name' => $name,
            'slug' => $slug,
            'plan' => 'free',
        ]), function (Tenant $tenant) use ($owner): void {
            $tenant->users()->attach($owner, [
                'role' => 'owner',
                'joined_at' => now(),
            ]);
        });
    }

    /**
     * Generate a unique slug from the given name.
     */
    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $count = 1;

        while (Tenant::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$count++;
        }

        return $slug;
    }
}
