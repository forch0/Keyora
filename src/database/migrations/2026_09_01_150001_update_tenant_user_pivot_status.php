<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Updates the tenant_user pivot to support member lifecycle:
 * - Changes `status` from a plain string to an enum (active, suspended, left)
 * - Adds `suspended_at` timestamp for audit trail
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenant_user', function (Blueprint $table) {
            // SQLite (test) doesn't support altering enum columns in-place,
            // so we drop + recreate. Postgres handles it natively, but
            // dropping+recreating is portable and the table is small.
            $table->dropColumn('status');
        });

        Schema::table('tenant_user', function (Blueprint $table) {
            $table->enum('status', ['active', 'suspended', 'left'])->default('active')->after('role');
            $table->timestamp('suspended_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_user', function (Blueprint $table) {
            $table->dropColumn(['suspended_at', 'status']);
        });

        Schema::table('tenant_user', function (Blueprint $table) {
            $table->string('status')->default('active')->after('role');
        });
    }
};
