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
        Schema::table('calls', function (Blueprint $table) {
            // Retell webhook data storage
            if (!Schema::hasColumn('calls', 'webhook_data')) {
                $table->json('webhook_data')->nullable()->after('raw_data');
            }
            
            // Agent version tracking
            if (!Schema::hasColumn('calls', 'agent_version')) {
                $table->integer('agent_version')->nullable()->after('agent_id');
            }
            
            // Retell cost tracking (in dollars)
            if (!Schema::hasColumn('calls', 'retell_cost')) {
                $table->decimal('retell_cost', 10, 4)->nullable()->after('cost');
            }
            
            // Custom SIP headers
            if (!Schema::hasColumn('calls', 'custom_sip_headers')) {
                $table->json('custom_sip_headers')->nullable()->after('metadata');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $columns = ['webhook_data', 'agent_version', 'retell_cost', 'custom_sip_headers'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('calls', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};