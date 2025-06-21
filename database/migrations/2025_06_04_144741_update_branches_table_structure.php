<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            // Neue Felder für erweiterte Funktionalität
            $this->addJsonColumn($table, 'retell_agent_cache', true);
            $table->timestamp('retell_last_sync')->nullable();
            $this->addJsonColumn($table, 'configuration_status', true);
            $this->addJsonColumn($table, 'parent_settings', true);
            $table->string('address')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('website')->nullable();
            $this->addJsonColumn($table, 'business_hours', true);
            $this->addJsonColumn($table, 'services_override', true);
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'retell_agent_cache',
                'retell_last_sync',
                'configuration_status',
                'parent_settings',
                'address',
                'postal_code',
                'website',
                'business_hours',
                'services_override'
            ]);
        });
    }
};
