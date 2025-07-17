<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Cleaning up white-label columns...\n";

try {
    // Simple SQL to drop columns
    $columns = [
        'parent_company_id',
        'company_type', 
        'is_white_label',
        'white_label_settings',
        'commission_rate'
    ];
    
    foreach ($columns as $column) {
        try {
            DB::statement("ALTER TABLE companies DROP COLUMN $column");
            echo "Dropped column: $column\n";
        } catch (\Exception $e) {
            echo "Column $column does not exist or error: " . $e->getMessage() . "\n";
        }
    }
    
    // Drop reseller_permissions table
    try {
        DB::statement("DROP TABLE IF EXISTS reseller_permissions");
        echo "Dropped reseller_permissions table\n";
    } catch (\Exception $e) {
        echo "Error dropping table: " . $e->getMessage() . "\n";
    }
    
    // Drop portal_users columns
    $portalColumns = ['can_access_child_companies', 'accessible_company_ids'];
    foreach ($portalColumns as $column) {
        try {
            DB::statement("ALTER TABLE portal_users DROP COLUMN $column");
            echo "Dropped portal_users column: $column\n";
        } catch (\Exception $e) {
            echo "Portal column $column does not exist or error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nCleanup complete!\n";
    
} catch (\Exception $e) {
    echo "General error: " . $e->getMessage() . "\n";
}