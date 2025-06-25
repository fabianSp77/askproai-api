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
        Schema::table('retell_agents', function (Blueprint $table) {
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('retell_agents', 'configuration')) {
                $table->json('configuration')->nullable()->after('name');
            }
            
            if (!Schema::hasColumn('retell_agents', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('is_active');
            }
            
            if (!Schema::hasColumn('retell_agents', 'sync_status')) {
                $table->string('sync_status')->default('pending')->after('last_synced_at');
                // Values: pending, syncing, synced, error
            }
            
            // Add index for faster queries
            $table->index(['company_id', 'sync_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retell_agents', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'sync_status']);
            
            if (Schema::hasColumn('retell_agents', 'sync_status')) {
                $table->dropColumn('sync_status');
            }
            
            if (Schema::hasColumn('retell_agents', 'last_synced_at')) {
                $table->dropColumn('last_synced_at');
            }
            
            if (Schema::hasColumn('retell_agents', 'configuration')) {
                $table->dropColumn('configuration');
            }
        });
    }
};