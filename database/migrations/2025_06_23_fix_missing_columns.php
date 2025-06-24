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
        // Add working_hours column to staff table
        if (!Schema::hasColumn('staff', 'working_hours')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->json('working_hours')->nullable()->after('active');
            });
        }
        
        // Add response_time column to mcp_metrics table
        if (Schema::hasTable('mcp_metrics') && !Schema::hasColumn('mcp_metrics', 'response_time')) {
            Schema::table('mcp_metrics', function (Blueprint $table) {
                $table->integer('response_time')->nullable()->after('service');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn('working_hours');
        });
        
        if (Schema::hasTable('mcp_metrics')) {
            Schema::table('mcp_metrics', function (Blueprint $table) {
                $table->dropColumn('response_time');
            });
        }
    }
};