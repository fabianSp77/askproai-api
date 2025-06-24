<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * List of tables to drop - verified as empty and unused
     */
    protected $tablesToDrop = [
        // Authentication related (not used - using users table)
        'oauth_access_tokens',
        'oauth_auth_codes',
        'oauth_clients',
        'oauth_personal_access_clients',
        'oauth_refresh_tokens',
        'password_reset_tokens',
        'personal_access_tokens',
        
        // Legacy/unused tables
        'activity_log',
        'agents',
        // 'api_call_logs', // WICHTIG: Wird für API Monitoring verwendet!
        'api_credentials',
        'api_health_logs',
        'appointment_locks',
        'availability_cache',
        'billing_periods',
        'booking_flow_logs',
        'branch_pricing_overrides',
        'branch_service',
        'branch_service_overrides',
        'branch_staff',
        'cache',
        'cache_locks',
        'calcom_bookings',
        'calendars',
        'calendar_event_types',
        'calendar_mappings',
        'company_pricings',
        'conversion_targets',
        'cookie_consents',
        'dashboard_configurations',
        'dummy_companies',
        'event_type_mappings',
        'gdpr_requests',
        'integrations',
        'invoices',
        'invoice_items',
        'invoice_items_flexible',
        'jobs',
        'knowledge_categories',
        'knowledge_documents',
        'master_services',
        'model_has_permissions',
        'notification_log',
        'onboarding_progress',
        'retell_agents',
        'retell_webhooks',
        'service_staff',
        'service_usage_logs',
        'slow_query_log',
        // 'staff_branches', // WICHTIG: Wird für Staff-Branch Many-to-Many Beziehung verwendet!
        'staff_branches_and_staff_services_tables',
        // 'staff_event_types', // WICHTIG: Wird für Mitarbeiter-EventType Zuordnung verwendet!
        // 'staff_services', // WICHTIG: Wird für Staff-Service Many-to-Many Beziehung verwendet!
        'staff_service_assignments_backup',
        'system_alerts',
        'tenants',
        'unified_event_types',
        'validation_results',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, verify all tables are empty before dropping
        $nonEmptyTables = [];
        
        foreach ($this->tablesToDrop as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                if ($count > 0) {
                    $nonEmptyTables[$table] = $count;
                }
            }
        }
        
        // If any tables have data, abort the migration
        if (!empty($nonEmptyTables)) {
            $message = "Cannot drop tables with existing data:\n";
            foreach ($nonEmptyTables as $table => $count) {
                $message .= "- $table: $count rows\n";
            }
            throw new \Exception($message);
        }
        
        // Disable foreign key checks temporarily
        Schema::disableForeignKeyConstraints();
        
        // Drop each table
        foreach ($this->tablesToDrop as $table) {
            if (Schema::hasTable($table)) {
                Schema::dropIfExists($table);
                echo "Dropped table: $table\n";
            }
        }
        
        // Re-enable foreign key checks
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We won't recreate these tables as they are unused
        // If needed, restore from backup
        echo "To restore dropped tables, please restore from database backup.\n";
    }
};