<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            // Neue Felder für erweiterte Funktionalität
            $table->json('retell_agent_cache')->nullable()->after('retell_agent_id');
            $table->timestamp('retell_last_sync')->nullable()->after('retell_agent_cache');
            $table->json('configuration_status')->nullable()->after('integration_status');
            $table->json('parent_settings')->nullable()->after('configuration_status');
            $table->string('address')->nullable()->after('city');
            $table->string('postal_code', 10)->nullable()->after('address');
            $table->string('website')->nullable()->after('notification_email');
            $table->json('business_hours')->nullable()->after('website');
            $table->json('services_override')->nullable()->after('business_hours');
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
