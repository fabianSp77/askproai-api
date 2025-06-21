<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$errors = [];
$warnings = [];
$fixes = [];

echo "\n\033[1;34m=== ASKPROAI COMPLETE SYSTEM ANALYSIS ===\033[0m\n\n";

// 1. Database Schema Analysis
echo "\033[1;33m1. DATABASE SCHEMA ANALYSIS\033[0m\n";

$expectedTables = [
    // Core Tables
    'companies' => ['id', 'name', 'slug', 'phone', 'email', 'website', 'description', 'is_active', 'settings', 'retell_api_key', 'calcom_api_key', 'industry', 'event_type_id', 'api_test_errors', 'created_at', 'updated_at'],
    'branches' => ['id', 'company_id', 'name', 'slug', 'address', 'city', 'postal_code', 'phone', 'email', 'is_active', 'calcom_event_type_id', 'calcom_user_id', 'retell_agent_id', 'business_hours', 'integrations_tested_at', 'created_at', 'updated_at'],
    'staff' => ['id', 'company_id', 'branch_id', 'first_name', 'last_name', 'email', 'phone', 'role', 'is_active', 'calcom_user_id', 'calendar_connected', 'calendar_provider', 'home_branch_id', 'created_at', 'updated_at'],
    'customers' => ['id', 'company_id', 'first_name', 'last_name', 'email', 'phone', 'mobile_app_user_id', 'mobile_app_device_token', 'mobile_app_preferences', 'notes', 'created_at', 'updated_at'],
    'appointments' => ['id', 'company_id', 'branch_id', 'customer_id', 'staff_id', 'service_id', 'call_id', 'start_time', 'end_time', 'status', 'notes', 'reminder_sent_at', 'reminder_type', 'calcom_event_type_id', 'calcom_booking_id', 'created_at', 'updated_at'],
    'calls' => ['id', 'company_id', 'branch_id', 'customer_id', 'appointment_id', 'phone_number', 'call_id', 'conversation_id', 'retell_call_id', 'calcom_booking_id', 'status', 'direction', 'duration_minutes', 'duration_sec', 'tags', 'sentiment', 'cost', 'transcription', 'recording_url', 'started_at', 'ended_at', 'created_at', 'updated_at'],
    'services' => ['id', 'company_id', 'branch_id', 'name', 'description', 'duration', 'price', 'is_active', 'calcom_event_type_id', 'created_at', 'updated_at'],
    'users' => ['id', 'name', 'email', 'email_verified_at', 'password', 'company_id', 'tenant_id', 'remember_token', 'created_at', 'updated_at'],
    
    // Integration Tables
    'integrations' => ['id', 'company_id', 'type', 'name', 'config', 'is_active', 'last_sync_at', 'created_at', 'updated_at'],
    'calcom_event_types' => ['id', 'company_id', 'event_type_id', 'title', 'slug', 'description', 'length', 'is_active', 'metadata', 'api_version', 'created_at', 'updated_at'],
    'unified_event_types' => ['id', 'company_id', 'branch_id', 'staff_id', 'calcom_event_type_id', 'service_id', 'is_active', 'assignment_status', 'import_source', 'imported_at', 'last_synced_at', 'created_at', 'updated_at'],
    
    // Phone Number Management
    'phone_numbers' => ['id', 'company_id', 'branch_id', 'phone_number', 'type', 'provider', 'is_active', 'capabilities', 'created_at', 'updated_at'],
    
    // Invoice & Pricing
    'invoices' => ['id', 'company_id', 'invoice_number', 'status', 'amount', 'tax_amount', 'total_amount', 'due_date', 'paid_at', 'creation_mode', 'payment_terms', 'stripe_invoice_id', 'created_at', 'updated_at'],
    'invoice_items' => ['id', 'invoice_id', 'description', 'quantity', 'unit_price', 'total_price', 'tax_rate', 'created_at', 'updated_at'],
    'company_pricings' => ['id', 'company_id', 'name', 'base_price', 'price_per_minute', 'price_per_appointment', 'free_minutes', 'free_appointments', 'is_active', 'created_at', 'updated_at'],
    
    // Tax Tables
    'tax_rates' => ['id', 'country', 'rate', 'name', 'is_active', 'created_at', 'updated_at'],
    
    // Security & GDPR
    'gdpr_requests' => ['id', 'customer_id', 'type', 'status', 'processed_at', 'expires_at', 'created_at', 'updated_at'],
    'security_logs' => ['id', 'user_id', 'ip_address', 'user_agent', 'action', 'threat_level', 'details', 'created_at'],
    'cookie_consents' => ['id', 'visitor_id', 'ip_address', 'consents', 'created_at', 'updated_at'],
    
    // Webhook & Monitoring
    'webhook_events' => ['id', 'company_id', 'type', 'source', 'event', 'payload', 'processed_at', 'error', 'created_at', 'updated_at'],
    'webhook_logs' => ['id', 'webhook_type', 'event_type', 'payload', 'status', 'response', 'error_message', 'created_at', 'updated_at'],
    
    // Knowledge Base
    'knowledge_categories' => ['id', 'name', 'slug', 'description', 'parent_id', 'order', 'is_active', 'created_at', 'updated_at'],
    'knowledge_documents' => ['id', 'category_id', 'title', 'slug', 'content', 'excerpt', 'status', 'author_id', 'views', 'is_featured', 'order', 'published_at', 'created_at', 'updated_at'],
    
    // Master Services
    'master_services' => ['id', 'name', 'category', 'default_duration', 'suggested_price', 'description', 'is_active', 'created_at', 'updated_at'],
    'branch_service_overrides' => ['id', 'branch_id', 'service_id', 'master_service_id', 'price', 'duration', 'is_active', 'created_at', 'updated_at'],
    
    // Pivot Tables
    'branch_staff' => ['branch_id', 'staff_id', 'created_at', 'updated_at'],
    'branch_service' => ['branch_id', 'service_id', 'created_at', 'updated_at'],
    'staff_services' => ['staff_id', 'service_id', 'created_at', 'updated_at'],
    'staff_event_types' => ['id', 'staff_id', 'calcom_event_type_id', 'is_active', 'created_at', 'updated_at'],
    
    // Session & Cache
    'sessions' => ['id', 'user_id', 'ip_address', 'user_agent', 'payload', 'last_activity'],
    'cache' => ['key', 'value', 'expiration'],
    
    // Jobs & Failed Jobs
    'jobs' => ['id', 'queue', 'payload', 'attempts', 'reserved_at', 'available_at', 'created_at'],
    'failed_jobs' => ['id', 'uuid', 'connection', 'queue', 'payload', 'exception', 'failed_at'],
    
    // Personal Access Tokens
    'personal_access_tokens' => ['id', 'tokenable_type', 'tokenable_id', 'name', 'token', 'abilities', 'last_used_at', 'expires_at', 'created_at', 'updated_at'],
];

foreach ($expectedTables as $table => $columns) {
    if (!Schema::hasTable($table)) {
        $errors[] = "Table '$table' is missing";
        $fixes[] = "CREATE TABLE `$table` (...)";
        continue;
    }
    
    foreach ($columns as $column) {
        if (!Schema::hasColumn($table, $column)) {
            $warnings[] = "Column '$column' missing in table '$table'";
            $fixes[] = "ALTER TABLE `$table` ADD COLUMN `$column` ...";
        }
    }
}

echo "✓ Analyzed " . count($expectedTables) . " tables\n";

// 2. Filament Resources Analysis
echo "\n\033[1;33m2. FILAMENT RESOURCES ANALYSIS\033[0m\n";

$resources = [
    'AppointmentResource' => App\Filament\Admin\Resources\AppointmentResource::class,
    'BranchResource' => App\Filament\Admin\Resources\BranchResource::class,
    'CallResource' => App\Filament\Admin\Resources\CallResource::class,
    'CompanyResource' => App\Filament\Admin\Resources\CompanyResource::class,
    'CustomerResource' => App\Filament\Admin\Resources\CustomerResource::class,
    'InvoiceResource' => App\Filament\Admin\Resources\InvoiceResource::class,
    'ServiceResource' => App\Filament\Admin\Resources\ServiceResource::class,
    'StaffResource' => App\Filament\Admin\Resources\StaffResource::class,
    'PhoneNumberResource' => App\Filament\Admin\Resources\PhoneNumberResource::class,
];

foreach ($resources as $name => $class) {
    if (!class_exists($class)) {
        $errors[] = "Resource class '$class' not found";
        continue;
    }
    
    try {
        $model = $class::getModel();
        if (!class_exists($model)) {
            $errors[] = "Model '$model' for resource '$name' not found";
        }
        
        // Check if can be instantiated
        $instance = new $class();
        
        // Check authorization
        if (method_exists($class, 'canViewAny')) {
            $user = User::first();
            if ($user && !$class::canViewAny($user)) {
                $warnings[] = "Resource '$name' may have permission issues";
            }
        }
    } catch (Exception $e) {
        $errors[] = "Resource '$name' error: " . $e->getMessage();
    }
}

echo "✓ Analyzed " . count($resources) . " resources\n";

// 3. Filament Pages Analysis
echo "\n\033[1;33m3. FILAMENT PAGES ANALYSIS\033[0m\n";

$pages = [
    'Dashboard' => App\Filament\Admin\Pages\Dashboard::class,
    'QuickSetupWizard' => App\Filament\Admin\Pages\QuickSetupWizard::class,
    'SystemHealthSimple' => App\Filament\Admin\Pages\SystemHealthSimple::class,
    'ApiHealthMonitor' => App\Filament\Admin\Pages\ApiHealthMonitor::class,
    'CalcomSyncStatus' => App\Filament\Admin\Pages\CalcomSyncStatus::class,
    'StaffEventAssignment' => App\Filament\Admin\Pages\StaffEventAssignment::class,
    'WebhookMonitor' => App\Filament\Admin\Pages\WebhookMonitor::class,
    'KnowledgeBaseManager' => App\Filament\Admin\Pages\KnowledgeBaseManager::class,
];

foreach ($pages as $name => $class) {
    if (!class_exists($class)) {
        $errors[] = "Page class '$class' not found";
        continue;
    }
    
    try {
        // Check if can be instantiated
        $instance = new $class();
        
        // Check authorization
        if (method_exists($class, 'canAccess')) {
            if (!$class::canAccess()) {
                $warnings[] = "Page '$name' may have access restrictions";
            }
        }
    } catch (Exception $e) {
        $errors[] = "Page '$name' error: " . $e->getMessage();
    }
}

echo "✓ Analyzed " . count($pages) . " pages\n";

// 4. Navigation Analysis
echo "\n\033[1;33m4. NAVIGATION ANALYSIS\033[0m\n";

$navigationItems = [
    '/admin' => 'Dashboard',
    '/admin/appointments' => 'Appointments',
    '/admin/customers' => 'Customers',
    '/admin/calls' => 'Calls',
    '/admin/companies' => 'Companies',
    '/admin/branches' => 'Branches',
    '/admin/staff' => 'Staff',
    '/admin/services' => 'Services',
    '/admin/invoices' => 'Invoices',
    '/admin/phone-numbers' => 'Phone Numbers',
];

foreach ($navigationItems as $url => $name) {
    // This is a basic check - in real scenario we'd need to actually test HTTP requests
    $resourceName = str_replace(['/admin/', '-'], ['', ''], $url);
    if ($resourceName && $resourceName !== 'admin') {
        $className = 'App\\Filament\\Admin\\Resources\\' . ucfirst($resourceName) . 'Resource';
        if (!class_exists($className)) {
            $warnings[] = "Navigation item '$name' ($url) may not work - resource not found";
        }
    }
}

echo "✓ Analyzed " . count($navigationItems) . " navigation items\n";

// 5. Model Relations & Scopes
echo "\n\033[1;33m5. MODEL RELATIONS & SCOPES\033[0m\n";

$models = [
    'Company' => App\Models\Company::class,
    'Branch' => App\Models\Branch::class,
    'Staff' => App\Models\Staff::class,
    'Customer' => App\Models\Customer::class,
    'Appointment' => App\Models\Appointment::class,
    'Call' => App\Models\Call::class,
    'Service' => App\Models\Service::class,
];

foreach ($models as $name => $class) {
    if (!class_exists($class)) {
        $errors[] = "Model '$class' not found";
        continue;
    }
    
    try {
        $instance = new $class();
        
        // Check if has company_id for tenant scoping
        if (Schema::hasColumn($instance->getTable(), 'company_id')) {
            $traits = class_uses_recursive($class);
            if (!isset($traits[\App\Traits\HasCompany::class]) && !isset($traits[\App\Traits\BelongsToCompany::class])) {
                $warnings[] = "Model '$name' has company_id but may not use tenant scoping trait";
            }
        }
    } catch (Exception $e) {
        $errors[] = "Model '$name' error: " . $e->getMessage();
    }
}

echo "✓ Analyzed " . count($models) . " models\n";

// 6. Permissions & Policies
echo "\n\033[1;33m6. PERMISSIONS & POLICIES\033[0m\n";

// Check for the specific 403 error on /admin/staff
$staffResourceClass = \App\Filament\Admin\Resources\StaffResource::class;
if (class_exists($staffResourceClass)) {
    try {
        // Check authorization
        $user = User::first();
        if ($user) {
            // Check canViewAny static method
            $canView = true;
            if (method_exists($staffResourceClass, 'canViewAny')) {
                try {
                    $canView = $staffResourceClass::canViewAny();
                } catch (Exception $e) {
                    // Method might require auth user
                    $errors[] = "StaffResource::canViewAny() error: " . $e->getMessage();
                    $canView = false;
                }
            }
            
            if (!$canView) {
                $errors[] = "StaffResource: Access denied (403 error) - canViewAny() returns false";
                $fixes[] = "Update StaffResource::canViewAny() to return true or implement proper authorization";
            } else {
                echo "✓ StaffResource authorization check passed\n";
            }
            
            // Check if getEloquentQuery might have issues
            if (method_exists($staffResourceClass, 'getEloquentQuery')) {
                echo "✓ StaffResource has getEloquentQuery method\n";
            }
        } else {
            $warnings[] = "No user found to test permissions";
        }
    } catch (Exception $e) {
        $errors[] = "StaffResource check failed: " . $e->getMessage();
    }
}

// Check all resources for potential 403 issues
echo "\nChecking all resources for authorization issues...\n";
foreach ($resources as $name => $class) {
    if (class_exists($class) && method_exists($class, 'canViewAny')) {
        try {
            $canView = $class::canViewAny();
            if (!$canView) {
                $errors[] = "$name: Access denied (403) - canViewAny() returns false";
            }
        } catch (Exception $e) {
            // Silently continue - method might require parameters
        }
    }
}

// Generate Summary & SQL Fixes
echo "\n\033[1;34m=== ANALYSIS SUMMARY ===\033[0m\n\n";

echo "\033[1;31mERRORS (" . count($errors) . "):\033[0m\n";
foreach ($errors as $error) {
    echo "❌ $error\n";
}

echo "\n\033[1;33mWARNINGS (" . count($warnings) . "):\033[0m\n";
foreach ($warnings as $warning) {
    echo "⚠️  $warning\n";
}

// Generate SQL fixes
$sqlFixes = [];

// Missing tables
if (strpos(implode('', $errors), "Table 'staff' is missing") !== false) {
    $sqlFixes[] = "
CREATE TABLE IF NOT EXISTS `staff` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'staff',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `calcom_user_id` varchar(255) DEFAULT NULL,
  `calendar_connected` tinyint(1) NOT NULL DEFAULT '0',
  `calendar_provider` varchar(50) DEFAULT NULL,
  `home_branch_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_company_id_index` (`company_id`),
  KEY `staff_branch_id_index` (`branch_id`),
  KEY `staff_email_index` (`email`),
  CONSTRAINT `staff_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
}

// Missing columns
foreach ($warnings as $warning) {
    if (preg_match("/Column '(.+)' missing in table '(.+)'/", $warning, $matches)) {
        $column = $matches[1];
        $table = $matches[2];
        
        // Generate appropriate ALTER TABLE statement based on column name
        $columnDef = match($column) {
            'company_id', 'branch_id', 'staff_id', 'customer_id', 'service_id' => "bigint unsigned NULL",
            'is_active', 'calendar_connected' => "tinyint(1) NOT NULL DEFAULT '1'",
            'email', 'phone', 'name', 'slug' => "varchar(255) DEFAULT NULL",
            'description', 'notes' => "text DEFAULT NULL",
            'settings', 'metadata', 'payload' => "json DEFAULT NULL",
            'created_at', 'updated_at' => "timestamp NULL DEFAULT NULL",
            'price', 'amount', 'total_amount' => "decimal(10,2) DEFAULT NULL",
            'duration', 'duration_minutes' => "int DEFAULT NULL",
            default => "varchar(255) DEFAULT NULL"
        };
        
        $sqlFixes[] = "ALTER TABLE `$table` ADD COLUMN IF NOT EXISTS `$column` $columnDef;";
    }
}

echo "\n\033[1;32mSQL FIXES:\033[0m\n";
echo implode("\n", $sqlFixes);

// Save fixes to file
file_put_contents('/var/www/api-gateway/system-fixes.sql', implode("\n", $sqlFixes));

echo "\n\n✅ Analysis complete. SQL fixes saved to system-fixes.sql\n";

// Generate PHP fixes
$phpFixes = "<?php\n\n";
$phpFixes .= "// Fix for StaffResource 403 error\n";
$phpFixes .= "// In app/Filament/Admin/Resources/StaffResource.php\n";
$phpFixes .= "// Add or update the canViewAny method:\n";
$phpFixes .= "/*\n";
$phpFixes .= "public static function canViewAny(): bool\n";
$phpFixes .= "{\n";
$phpFixes .= "    return true; // Or implement proper authorization logic\n";
$phpFixes .= "}\n";
$phpFixes .= "*/\n\n";

file_put_contents('/var/www/api-gateway/system-fixes.php', $phpFixes);

echo "\nPHP fixes saved to system-fixes.php\n";