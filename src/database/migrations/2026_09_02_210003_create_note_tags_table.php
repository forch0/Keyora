<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('note_tags', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('color')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'name']);
            $table->index('user_id');
        });

        Schema::create('secure_note_tag', function (Blueprint $table): void {
            $table->foreignId('note_id')->constrained('secure_notes')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('note_tags')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['note_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secure_note_tag');
        Schema::dropIfExists('note_tags');
    }
};
