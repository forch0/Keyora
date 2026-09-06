<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secure_links', function (Blueprint $table): void {
            $table->bigIncrements('id');

            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();

            // Polymorphic resource
            $table->string('resource_type');
            $table->unsignedBigInteger('resource_id');

            $table->foreignId('created_by')->constrained('users');

            $table->string('recipient_email')->nullable();
            $table->string('password_hash')->nullable();
            $table->string('otp_code_hash')->nullable();
            $table->timestamp('otp_sent_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();

            $table->enum('permission', ['view', 'download'])->default('view');
            $table->boolean('download_enabled')->default(true);

            $table->timestamp('expires_at')->nullable();
            $table->integer('first_view_expires_hours')->nullable();
            $table->integer('max_views')->nullable();
            $table->integer('views_count')->default(0);
            $table->timestamp('first_viewed_at')->nullable();
            $table->boolean('is_one_time')->default(false);

            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users');
            $table->string('revoke_reason')->nullable();

            $table->timestamps();

            $table->index('tenant_id');
            $table->index('uuid');
            $table->index(['resource_type', 'resource_id']);
            $table->index('created_by');
            $table->index('expires_at');
            $table->index('revoked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secure_links');
    }
};
