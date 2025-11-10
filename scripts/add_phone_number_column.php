<?php

/**
 * Add phone_number column to retell_call_sessions table
 * 
 * Issue: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'phone_number'
 * Call: call_5a2607924c109b148edf38a38b7 (08:26:25)
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

echo "🔧 ADD PHONE_NUMBER COLUMN TO retell_call_sessions\n";
echo str_repeat('=', 80) . "\n\n";

try {
    echo "Checking retell_call_sessions table...\n";
    
    if (!Schema::hasTable('retell_call_sessions')) {
        echo "   ⚠️ retell_call_sessions table doesn't exist\n";
        exit(1);
    }
    
    $columns = Schema::getColumnListing('retell_call_sessions');
    
    if (!in_array('phone_number', $columns)) {
        echo "   ❌ phone_number column doesn't exist\n";
        echo "   ✅ Adding phone_number column...\n";
        
        Schema::table('retell_call_sessions', function (Blueprint $table) {
            $table->string('phone_number', 50)->nullable()->after('branch_id');
        });
        
        echo "   ✅ Fixed: phone_number column added\n\n";
    } else {
        echo "   ✅ phone_number column already exists\n\n";
    }
    
    echo str_repeat('=', 80) . "\n";
    echo "✅ DATABASE SCHEMA FIX COMPLETE\n";
    echo str_repeat('=', 80) . "\n\n";
    
    echo "Next step: Reload PHP-FPM and test!\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
