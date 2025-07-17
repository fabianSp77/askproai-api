<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Cleaning up partial migration...\n";

try {
    // Check and remove columns if they exist
    if (Schema::hasColumn('companies', 'parent_company_id')) {
        Schema::table('companies', function (Blueprint $table) {
            try {
                $table->dropForeign(['parent_company_id']);
            } catch (\Exception $e) {
                // Foreign key might not exist
            }
            $table->dropColumn('parent_company_id');
        });
        echo "Removed parent_company_id from companies\n";
    }
    
    if (Schema::hasColumn('companies', 'company_type')) {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('company_type');
        });
        echo "Removed company_type from companies\n";
    }
    
    if (Schema::hasColumn('companies', 'is_white_label')) {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('is_white_label');
        });
        echo "Removed is_white_label from companies\n";
    }
    
    if (Schema::hasColumn('companies', 'white_label_settings')) {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('white_label_settings');
        });
        echo "Removed white_label_settings from companies\n";
    }
    
    if (Schema::hasColumn('companies', 'commission_rate')) {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('commission_rate');
        });
        echo "Removed commission_rate from companies\n";
    }
    
    // Drop reseller_permissions table if exists
    if (Schema::hasTable('reseller_permissions')) {
        Schema::dropIfExists('reseller_permissions');
        echo "Dropped reseller_permissions table\n";
    }
    
    // Remove portal_users columns if they exist
    if (Schema::hasColumn('portal_users', 'can_access_child_companies')) {
        Schema::table('portal_users', function (Blueprint $table) {
            $table->dropColumn('can_access_child_companies');
        });
        echo "Removed can_access_child_companies from portal_users\n";
    }
    
    if (Schema::hasColumn('portal_users', 'accessible_company_ids')) {
        Schema::table('portal_users', function (Blueprint $table) {
            $table->dropColumn('accessible_company_ids');
        });
        echo "Removed accessible_company_ids from portal_users\n";
    }
    
    echo "\nCleanup complete! You can now run the migration again.\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}