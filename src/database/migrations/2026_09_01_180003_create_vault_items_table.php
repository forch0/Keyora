<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vault_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['password', 'api_key', 'server', 'database', 'note']);
            $table->text('username')->nullable();
            $table->text('password')->nullable();
            $table->string('url')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->text('custom_fields')->nullable();
            $table->boolean('favorite')->default(false);
            $table->foreignId('folder_id')->nullable()->constrained('vault_folders')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'team_id']);
            $table->index(['tenant_id', 'team_id', 'type']);
            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vault_items');
    }
};
