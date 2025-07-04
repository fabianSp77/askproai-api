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
        // Add progress and duration_ms to command_executions if not exists
        Schema::table('command_executions', function (Blueprint $table) {
            if (!Schema::hasColumn('command_executions', 'progress')) {
                $table->integer('progress')->default(0)->after('status');
            }
            if (!Schema::hasColumn('command_executions', 'duration_ms')) {
                $table->integer('duration_ms')->nullable()->after('execution_time_ms');
            }
        });
        
        // Add current_command_index and duration_ms to workflow_executions if not exists
        Schema::table('workflow_executions', function (Blueprint $table) {
            if (!Schema::hasColumn('workflow_executions', 'current_command_index')) {
                $table->integer('current_command_index')->default(0)->after('current_step');
            }
            if (!Schema::hasColumn('workflow_executions', 'duration_ms')) {
                $table->integer('duration_ms')->nullable()->after('execution_time_ms');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('command_executions', function (Blueprint $table) {
            $table->dropColumn(['progress', 'duration_ms']);
        });
        
        Schema::table('workflow_executions', function (Blueprint $table) {
            $table->dropColumn(['current_command_index', 'duration_ms']);
        });
    }
};