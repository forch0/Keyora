<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_grants', function (Blueprint $table): void {
            $table->bigIncrements('id');

            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // Polymorphic grantable (VaultItem, SecureFile, SecureNote, Folder, ...)
            $table->string('grantable_type');
            $table->unsignedBigInteger('grantable_id');

            // Polymorphic subject (User, Team, Tenant)
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');

            // Permission level
            $table->enum('permission', ['view', 'download', 'edit', 'share', 'manage']);

            // Temporal constraints
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->boolean('start_on_first_view')->default(false);
            $table->timestamp('first_viewed_at')->nullable();

            // View-count constraints
            $table->integer('max_views')->nullable();
            $table->integer('views_count')->default(0);

            // Audit / revocation
            $table->foreignId('granted_by')->constrained('users');
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users');
            $table->string('revoke_reason')->nullable();

            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['grantable_type', 'grantable_id']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('expires_at');
            $table->index('revoked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_grants');
    }
};
