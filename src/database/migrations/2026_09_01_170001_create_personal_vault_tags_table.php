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
        Schema::create('personal_vault_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'name']);
            $table->index('user_id');
        });

        Schema::create('personal_vault_item_tag', function (Blueprint $table) {
            $table->foreignId('item_id')->constrained('personal_vault_items')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('personal_vault_tags')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['item_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_vault_item_tag');
        Schema::dropIfExists('personal_vault_tags');
    }
};
