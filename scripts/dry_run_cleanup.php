<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== DRY RUN: DATABASE CLEANUP ===\n\n";

$tablesToDrop = [
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

$existingTables = 0;
$missingTables = 0;

echo "Tables that WILL BE DROPPED:\n";
echo str_repeat("-", 50) . "\n";

foreach ($tablesToDrop as $table) {
    if (Schema::hasTable($table)) {
        $count = DB::table($table)->count();
        echo "✓ {$table} (Records: {$count})\n";
        $existingTables++;
    } else {
        echo "✗ {$table} (NOT FOUND)\n";
        $missingTables++;
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "SUMMARY:\n";
echo "Tables to drop: " . count($tablesToDrop) . "\n";
echo "Existing tables: {$existingTables}\n";
echo "Missing tables: {$missingTables}\n";

// Show what will remain
$allTables = DB::select('SHOW TABLES');
$tableField = 'Tables_in_' . env('DB_DATABASE', 'askproai_db');
$currentTableCount = count($allTables);
$remainingCount = $currentTableCount - $existingTables;

echo "\nCurrent total tables: {$currentTableCount}\n";
echo "After cleanup: ~{$remainingCount} tables\n";
echo "Reduction: " . round(($existingTables / $currentTableCount) * 100, 1) . "%\n";