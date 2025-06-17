<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * List of tables to drop
     */
    private array $tablesToDrop = [
        // Reservation System (12 tables)
        'reservation_accessories',
        'reservation_color_rules',
        'reservation_files',
        'reservation_guests',
        'reservation_instances',
        'reservation_reminders',
        'reservation_resources',
        'reservation_series',
        'reservation_statuses',
        'reservation_types',
        'reservation_users',
        'reservation_waitlist_requests',
        
        // Resource Management (14 tables)
        'resources',
        'resource_accessories',
        'resource_group_assignment',
        'resource_groups',
        'resource_status_reasons',
        'resource_type_assignment',
        'resource_types',
        'accessories',
        'blackout_instances',
        'blackout_series',
        'blackout_series_resources',
        'peak_times',
        'quotas',
        'schedules',
        
        // OAuth System (5 tables)
        'oauth_access_tokens',
        'oauth_auth_codes',
        'oauth_clients',
        'oauth_personal_access_clients',
        'oauth_refresh_tokens',
        
        // Announcement System (3 tables)
        'announcements',
        'announcement_groups',
        'announcement_resources',
        
        // Custom Attributes (4 tables)
        'custom_attributes',
        'custom_attribute_categories',
        'custom_attribute_entities',
        'custom_attribute_values',
        
        // User Management Overkill (8 tables)
        'user_email_preferences',
        'user_groups',
        'user_preferences',
        'user_resource_permissions',
        'user_session',
        'user_statuses',
        'group_resource_permissions',
        'group_roles',
        'groups',
        
        // Redundant/Old tables
        'agents',
        'account_activation',
        'activity_log',
        'api_health_logs',
        'business_hours_templates',
        'calendar_mappings',
        'calendars',
        'conversion_targets',
        'dashboard_configurations',
        'dbversion',
        'dummy_companies',
        'event_type_mappings',
        'kunden',
        'laravel_users',
        'layouts',
        'master_services',
        'notes',
        'notification_log',
        'reseller_tenant',
        'roles_old',
        'saved_reports',
        'slow_query_log',
        'staff_branches_and_staff_services_tables',
        'staff_service_assignments_backup',
        // 'tenants', // Keep for now - has dependency from reseller_tenant
        'tests',
        'time_blocks',
        'validation_results',
        
        // Old integrations
        'retell_agents',
        'retell_webhooks',
        'integrations',
        'api_credentials',
        'onboarding_progress',
        'event_type_import_logs',
        'unified_event_types',
        'staff_event_type_assignments',
        'service_staff',
        'staff_branches',
        'branch_service',
        'branch_service_overrides',
        'password_reset_tokens',
        'sessions',
        'reminders'
    ];
    
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Log::info('Starting database cleanup migration');
        
        // Disable foreign key constraints
        Schema::disableForeignKeyConstraints();
        
        $droppedCount = 0;
        
        foreach ($this->tablesToDrop as $table) {
            if (Schema::hasTable($table)) {
                try {
                    Schema::dropIfExists($table);
                    $droppedCount++;
                    Log::info("Dropped table: {$table}");
                } catch (\Exception $e) {
                    Log::warning("Could not drop table {$table}: " . $e->getMessage());
                }
            }
        }
        
        // Re-enable foreign key constraints
        Schema::enableForeignKeyConstraints();
        
        Log::info("Database cleanup complete. Dropped {$droppedCount} tables.");
        
        // Log remaining tables
        $remainingTables = DB::select('SHOW TABLES');
        $tableCount = count($remainingTables);
        Log::info("Remaining tables: {$tableCount}");
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible
        // The dropped tables would need to be recreated from their original migrations
        Log::warning('Database cleanup migration cannot be reversed. Tables must be restored from backup.');
    }
};