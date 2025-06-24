<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add status column to mcp_metrics table
        if (Schema::hasTable('mcp_metrics') && !Schema::hasColumn('mcp_metrics', 'status')) {
            Schema::table('mcp_metrics', function (Blueprint $table) {
                $table->string('status', 50)->nullable()->after('response_time');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('mcp_metrics')) {
            Schema::table('mcp_metrics', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};