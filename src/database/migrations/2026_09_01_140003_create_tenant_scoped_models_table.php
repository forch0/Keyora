<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Test-only migration: creates a table for a tenant-scoped model
 * used to verify the BelongsToTenant trait behavior in tests.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_scoped_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_scoped_models');
    }
};
