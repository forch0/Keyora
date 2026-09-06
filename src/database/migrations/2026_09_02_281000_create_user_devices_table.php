<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table): void {
            $table->bigIncrements('id');

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_fingerprint');
            $table->string('browser');
            $table->string('os');
            $table->string('device_type');
            $table->string('ip_address');
            $table->timestamp('last_seen_at');
            $table->timestamp('first_seen_at');
            $table->timestamps();

            $table->unique(['user_id', 'device_fingerprint']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
