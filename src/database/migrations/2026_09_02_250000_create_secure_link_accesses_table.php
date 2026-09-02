<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secure_link_accesses', function (Blueprint $table): void {
            $table->bigIncrements('id');

            $table->foreignId('secure_link_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address');
            $table->string('user_agent');
            $table->string('email')->nullable();
            $table->timestamp('accessed_at');

            $table->timestamps();

            $table->index('secure_link_id');
            $table->index('accessed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secure_link_accesses');
    }
};
