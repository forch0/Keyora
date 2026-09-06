<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_views', function (Blueprint $table): void {
            $table->bigIncrements('id');

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('resource_type');
            $table->unsignedBigInteger('resource_id');
            $table->timestamp('viewed_at');

            $table->timestamps();

            $table->index(['user_id', 'viewed_at']);
            $table->index(['resource_type', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_views');
    }
};
