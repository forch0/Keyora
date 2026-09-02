<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'personal_vault_items',
            'teams',
            'access_grants',
            'access_requests',
            'secure_links',
            'security_alerts',
            'user_devices',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->softDeletes()->after('updated_at');
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'personal_vault_items',
            'teams',
            'access_grants',
            'access_requests',
            'secure_links',
            'security_alerts',
            'user_devices',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
