<?php

/**
 * DIRECT RESOURCE TEST: Test all 36 Filament Resources directly
 * This simulates what happens when a user loads each resource page
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DIRECT RESOURCE TEST ===\n";
echo "Testing all 36 Filament Resources\n\n";

// All known resources
$resources = [
    'ActivityLog' => \App\Filament\Resources\ActivityLogResource::class,
    'AdminUpdate' => \App\Filament\Resources\AdminUpdateResource::class,
    'AppointmentModification' => \App\Filament\Resources\AppointmentModificationResource::class,
    'Appointment' => \App\Filament\Resources\AppointmentResource::class,
    'BalanceBonusTier' => \App\Filament\Resources\BalanceBonusTierResource::class,
    'BalanceTopup' => \App\Filament\Resources\BalanceTopupResource::class,
    'Branch' => \App\Filament\Resources\BranchResource::class,
    'Call' => \App\Filament\Resources\CallResource::class,
    'CallbackRequest' => \App\Filament\Resources\CallbackRequestResource::class,
    'CompanyAssignmentConfig' => \App\Filament\Resources\CompanyAssignmentConfigResource::class,
    'Company' => \App\Filament\Resources\CompanyResource::class,
    'ConversationFlow' => \App\Filament\Resources\ConversationFlowResource::class,
    'CurrencyExchangeRate' => \App\Filament\Resources\CurrencyExchangeRateResource::class,
    'CustomerNote' => \App\Filament\Resources\CustomerNoteResource::class,
    'Customer' => \App\Filament\Resources\CustomerResource::class,
    'Integration' => \App\Filament\Resources\IntegrationResource::class,
    'Invoice' => \App\Filament\Resources\InvoiceResource::class,
    'NotificationConfiguration' => \App\Filament\Resources\NotificationConfigurationResource::class,
    'NotificationQueue' => \App\Filament\Resources\NotificationQueueResource::class,
    'NotificationTemplate' => \App\Filament\Resources\NotificationTemplateResource::class,
    'Permission' => \App\Filament\Resources\PermissionResource::class,
    'PhoneNumber' => \App\Filament\Resources\PhoneNumberResource::class,
    'PlatformCost' => \App\Filament\Resources\PlatformCostResource::class,
    'PolicyConfiguration' => \App\Filament\Resources\PolicyConfigurationResource::class,
    'PricingPlan' => \App\Filament\Resources\PricingPlanResource::class,
    'RetellAgent' => \App\Filament\Resources\RetellAgentResource::class,
    'RetellCallSession' => \App\Filament\Resources\RetellCallSessionResource::class,
    'Role' => \App\Filament\Resources\RoleResource::class,
    'Service' => \App\Filament\Resources\ServiceResource::class,
    'ServiceStaffAssignment' => \App\Filament\Resources\ServiceStaffAssignmentResource::class,
    'Staff' => \App\Filament\Resources\StaffResource::class,
    'SystemSettings' => \App\Filament\Resources\SystemSettingsResource::class,
    'Tenant' => \App\Filament\Resources\TenantResource::class,
    'Transaction' => \App\Filament\Resources\TransactionResource::class,
    'User' => \App\Filament\Resources\UserResource::class,
    'WorkingHour' => \App\Filament\Resources\WorkingHourResource::class,
];

$user = \App\Models\User::where('email', 'admin@askproai.de')->first();
auth()->login($user);
echo "✅ Logged in as: {$user->email}\n\n";

$passed = 0;
$failed = 0;
$results = [];

foreach ($resources as $name => $class) {
    echo str_pad($name, 40, ' ');

    if (!class_exists($class)) {
        echo "❌ CLASS NOT FOUND\n";
        $failed++;
        $results[$name] = ['status' => 'class_not_found'];
        continue;
    }

    try {
        // Get the model class
        $model = $class::getModel();

        // Try to get table query (this is what happens when page loads)
        $query = $class::getEloquentQuery();

        // Get first 10 records (simulates table loading)
        $records = $query->limit(10)->get();

        // Try to get navigation badge (if exists)
        try {
            $badge = $class::getNavigationBadge();
        } catch (\Exception $e) {
            // Badge errors are non-critical
        }

        echo "✅ OK ({$records->count()} records)\n";
        $passed++;
        $results[$name] = [
            'status' => 'ok',
            'records' => $records->count(),
            'model' => $model
        ];

    } catch (\Illuminate\Database\QueryException $e) {
        $errorMsg = $e->getMessage();

        if (preg_match("/Table '.*?\.(\w+)' doesn't exist/", $errorMsg, $matches)) {
            $table = $matches[1];
            echo "❌ MISSING TABLE: {$table}\n";
            $results[$name] = ['status' => 'missing_table', 'table' => $table];
        } elseif (preg_match("/Unknown column '(.*?)'/", $errorMsg, $matches)) {
            $column = $matches[1];
            echo "❌ MISSING COLUMN: {$column}\n";
            $results[$name] = ['status' => 'missing_column', 'column' => $column];
        } else {
            echo "❌ SQL ERROR\n";
            echo "   " . substr($errorMsg, 0, 100) . "...\n";
            $results[$name] = ['status' => 'sql_error', 'error' => substr($errorMsg, 0, 200)];
        }
        $failed++;

    } catch (\Exception $e) {
        echo "❌ PHP ERROR\n";
        echo "   {$e->getMessage()}\n";
        echo "   {$e->getFile()}:{$e->getLine()}\n";
        $results[$name] = [
            'status' => 'php_error',
            'error' => $e->getMessage(),
            'location' => "{$e->getFile()}:{$e->getLine()}"
        ];
        $failed++;
    }
}

echo "\n=== SUMMARY ===\n";
echo "✅ Passed: {$passed}/36\n";
echo "❌ Failed: {$failed}/36\n\n";

// Detailed error breakdown
$missingTables = [];
$missingColumns = [];
$phpErrors = [];

foreach ($results as $name => $result) {
    if ($result['status'] === 'missing_table') {
        $missingTables[$result['table']][] = $name;
    } elseif ($result['status'] === 'missing_column') {
        $missingColumns[$result['column']][] = $name;
    } elseif ($result['status'] === 'php_error') {
        $phpErrors[] = $name;
    }
}

if (!empty($missingTables)) {
    echo "=== MISSING TABLES ===\n";
    foreach ($missingTables as $table => $resources) {
        echo "Table '{$table}' needed by: " . implode(', ', $resources) . "\n";
    }
    echo "\n";
}

if (!empty($missingColumns)) {
    echo "=== MISSING COLUMNS ===\n";
    foreach ($missingColumns as $column => $resources) {
        echo "Column '{$column}' needed by: " . implode(', ', $resources) . "\n";
    }
    echo "\n";
}

if (!empty($phpErrors)) {
    echo "=== PHP ERRORS ===\n";
    foreach ($results as $name => $result) {
        if ($result['status'] === 'php_error') {
            echo "{$name}:\n";
            echo "  {$result['error']}\n";
            echo "  {$result['location']}\n\n";
        }
    }
}

// Save results
file_put_contents('resource_test_results.json', json_encode($results, JSON_PRETTY_PRINT));
echo "Detailed results: resource_test_results.json\n";

exit($failed > 0 ? 1 : 0);
