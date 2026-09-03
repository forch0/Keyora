<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add composite indexes for dashboard and access-resolution queries.
     *
     * Several indexes from the TODO list already exist:
     *  - personal_vault_items: (user_id, archived_at) — in create migration
     *  - personal_vault_items: (user_id, last_accessed_at) — in add_folder migration
     *  - activity_logs: (tenant_id, created_at) — in create migration
     *  - security_alerts: (user_id, read_at) — in create migration
     *  - secure_files/secure_notes: (tenant_id, ...) composites cover tenant_id-only lookups
     *
     * The missing ones are on access_grants, where the dashboard and resolver
     * frequently filter by revoked_at + expires_at alongside subject or tenant.
     */
    public function up(): void
    {
        Schema::table('access_grants', function (Blueprint $table) {
            $table->index(['subject_type', 'subject_id', 'revoked_at'], 'access_grants_subject_revoked_index');
            $table->index(['tenant_id', 'revoked_at', 'expires_at'], 'access_grants_tenant_revoked_expires_index');
        });
    }

    public function down(): void
    {
        Schema::table('access_grants', function (Blueprint $table) {
            $table->dropIndex('access_grants_subject_revoked_index');
            $table->dropIndex('access_grants_tenant_revoked_expires_index');
        });
    }
};
