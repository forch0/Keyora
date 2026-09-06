<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secure_files', function (Blueprint $table): void {
            $table->bigIncrements('id');

            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('file_folders')->nullOnDelete();

            $table->string('name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->bigInteger('size');
            $table->string('checksum');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('download_enabled')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('archived_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'team_id']);
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'folder_id']);
            $table->index('checksum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secure_files');
    }
};
