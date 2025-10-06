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
        
        if (!Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            // Add team-related fields if they don't exist
            if (!Schema::hasColumn('companies', 'calcom_team_name')) {
                $table->string('calcom_team_name')->nullable();
            }
            if (!Schema::hasColumn('companies', 'team_sync_status')) {
                $table->enum('team_sync_status', ['pending', 'syncing', 'synced', 'error'])->default('pending');
            }
            if (!Schema::hasColumn('companies', 'last_team_sync')) {
                $table->timestamp('last_team_sync')->nullable();
            }
            if (!Schema::hasColumn('companies', 'team_sync_error')) {
                $table->text('team_sync_error')->nullable();
            }
            if (!Schema::hasColumn('companies', 'team_member_count')) {
                $table->integer('team_member_count')->default(0);
            }
            if (!Schema::hasColumn('companies', 'team_event_type_count')) {
                $table->integer('team_event_type_count')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Drop columns if they exist
            Schema::hasColumn('companies', 'calcom_team_name') && $table->dropColumn('calcom_team_name');
            Schema::hasColumn('companies', 'team_sync_status') && $table->dropColumn('team_sync_status');
            Schema::hasColumn('companies', 'last_team_sync') && $table->dropColumn('last_team_sync');
            Schema::hasColumn('companies', 'team_sync_error') && $table->dropColumn('team_sync_error');
            Schema::hasColumn('companies', 'team_member_count') && $table->dropColumn('team_member_count');
            Schema::hasColumn('companies', 'team_event_type_count') && $table->dropColumn('team_event_type_count');
        });
    }
};
