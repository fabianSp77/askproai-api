<?php

/**
 * Add branch_name column to retell_call_sessions table
 * 
 * Issue: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'branch_name'
 * Calls: call_c6e6270699615c52586ca5efae9 (09:41:25 + 09:41:47)
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

echo "🔧 ADD BRANCH_NAME COLUMN TO retell_call_sessions\n";
echo str_repeat('=', 80) . "\n\n";

try {
    echo "Checking retell_call_sessions table...\n";
    
    if (!Schema::hasTable('retell_call_sessions')) {
        echo "   ⚠️ retell_call_sessions table doesn't exist\n";
        exit(1);
    }
    
    $columns = Schema::getColumnListing('retell_call_sessions');
    
    if (!in_array('branch_name', $columns)) {
        echo "   ❌ branch_name column doesn't exist\n";
        echo "   ✅ Adding branch_name column...\n";
        
        Schema::table('retell_call_sessions', function (Blueprint $table) {
            $table->string('branch_name', 255)->nullable()->after('phone_number');
        });
        
        echo "   ✅ Fixed: branch_name column added\n\n";
    } else {
        echo "   ✅ branch_name column already exists\n\n";
    }
    
    echo str_repeat('=', 80) . "\n";
    echo "✅ DATABASE SCHEMA FIX COMPLETE\n";
    echo str_repeat('=', 80) . "\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
