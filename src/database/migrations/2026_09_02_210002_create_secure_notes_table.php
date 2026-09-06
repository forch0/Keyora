<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secure_notes', function (Blueprint $table): void {
            $table->bigIncrements('id');

            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('note_folders')->nullOnDelete();

            $table->string('title');
            $table->longText('content');
            $table->string('content_format')->default('markdown');
            $table->boolean('is_pinned')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'team_id']);
            $table->index('user_id');
            $table->index(['tenant_id', 'folder_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secure_notes');
    }
};
