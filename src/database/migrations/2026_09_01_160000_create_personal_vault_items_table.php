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
        Schema::create('personal_vault_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['password', 'api_key', 'server', 'database']);
            $table->text('username')->nullable();
            $table->text('password')->nullable();
            $table->string('url')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->text('custom_fields')->nullable();
            $table->boolean('favorite')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'favorite']);
            $table->index(['user_id', 'archived_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_vault_items');
    }
};
