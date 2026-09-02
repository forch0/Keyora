<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_grants', function (Blueprint $table): void {
            $table->timestamp('warning_sent_at')->nullable()->after('first_viewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('access_grants', function (Blueprint $table): void {
            $table->dropColumn('warning_sent_at');
        });
    }
};
