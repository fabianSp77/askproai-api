<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            if (!Schema::hasColumn('calls', 'sentiment_score')) {
                $table->float('sentiment_score')->nullable()->after('cost_cents');
            }
            if (!Schema::hasColumn('calls', 'wait_time_sec')) {
                $table->integer('wait_time_sec')->nullable()->after('duration_sec');
            }
        });
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn(['sentiment_score', 'wait_time_sec']);
        });
    }
};