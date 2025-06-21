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
        Schema::table('companies', function (Blueprint $table) {
            // Add fields only if they don't exist
            if (!Schema::hasColumn('companies', 'retell_webhook_url')) {
                $table->string('retell_webhook_url')->nullable();
            }
            
            if (!Schema::hasColumn('companies', 'retell_agent_id')) {
                $table->string('retell_agent_id')->nullable();
            }
            
            if (!Schema::hasColumn('companies', 'calcom_calendar_mode')) {
                $table->enum('calcom_calendar_mode', ['zentral', 'filiale', 'mitarbeiter'])
                    ->default('zentral')
                    ;
            }
            
            if (!Schema::hasColumn('companies', 'billing_status')) {
                $table->enum('billing_status', ['active', 'inactive', 'trial', 'suspended'])
                    ->default('trial')
                    ;
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['retell_webhook_url', 'retell_agent_id', 'calcom_calendar_mode', 'billing_status']);
        });
    }
};
