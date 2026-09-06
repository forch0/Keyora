<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_requests', function (Blueprint $table): void {
            $table->bigIncrements('id');

            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();

            // Polymorphic resource
            $table->string('resource_type');
            $table->unsignedBigInteger('resource_id');

            $table->foreignId('resource_owner_id')->constrained('users');

            $table->enum('requested_permission', ['view', 'download', 'edit', 'share', 'manage']);
            $table->enum('granted_permission', ['view', 'download', 'edit', 'share', 'manage'])->nullable();
            $table->string('requested_duration')->nullable();
            $table->timestamp('granted_expires_at')->nullable();
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled', 'expired'])->default('pending');

            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();

            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['requester_id', 'status']);
            $table->index(['resource_owner_id', 'status']);
            $table->index(['resource_type', 'resource_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_requests');
    }
};
