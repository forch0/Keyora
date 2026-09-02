<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('note_folders', function (Blueprint $table): void {
            $table->bigIncrements('id');

            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('note_folders')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();

            $table->index(['tenant_id', 'team_id']);
            $table->index(['tenant_id', 'parent_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('note_folders');
    }
};
