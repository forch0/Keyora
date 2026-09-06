<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_invitations', function (Blueprint $table): void {
            $table->json('team_ids')->nullable()->after('role');
            $table->json('initial_access')->nullable()->after('team_ids');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_invitations', function (Blueprint $table): void {
            $table->dropColumn(['team_ids', 'initial_access']);
        });
    }
};
