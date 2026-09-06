<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('personal_vault_items', function (Blueprint $table) {
            $table->foreignId('folder_id')->nullable()->constrained('personal_vault_folders')->nullOnDelete();
            $table->timestamp('last_accessed_at')->nullable()->after('archived_at');

            $table->index(['user_id', 'folder_id']);
            $table->index(['user_id', 'last_accessed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_vault_items', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'last_accessed_at']);
            $table->dropIndex(['user_id', 'folder_id']);
            $table->dropColumn(['last_accessed_at', 'folder_id']);
        });
    }
};
