<?php

// Test View pages directly by simulating HTTP requests

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\User;
use App\Models\PhoneNumber;
use App\Models\RetellAgent;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Login as admin
$admin = User::where('email', 'admin@askproai.de')->first();
Auth::login($admin);

echo "Testing View Pages Directly\n";
echo "===========================\n\n";

// Test PhoneNumber View
echo "1. Testing PhoneNumber View Page:\n";
$phoneNumber = PhoneNumber::first();
if ($phoneNumber) {
    echo "   Record ID: {$phoneNumber->id}\n";
    
    try {
        $viewPageClass = 'App\Filament\Admin\Resources\PhoneNumberResource\Pages\ViewPhoneNumber';
        
        if (class_exists($viewPageClass)) {
            echo "   ✅ ViewPhoneNumber class exists\n";
            
            // Try to instantiate the page
            $page = new $viewPageClass();
            
            // Check if mount method exists
            if (method_exists($page, 'mount')) {
                echo "   ✅ mount() method exists\n";
            }
            
            // Check resource
            $resource = $page::getResource();
            echo "   Resource: " . $resource . "\n";
            
            // Check if infolist is defined
            if (method_exists($resource, 'infolist')) {
                echo "   ✅ Resource has infolist() method\n";
                
                // Try to get the infolist
                try {
                    $infolist = $resource::infolist(new \Filament\Infolists\Infolist($phoneNumber));
                    echo "   ✅ Infolist can be created\n";
                } catch (Exception $e) {
                    echo "   ❌ Error creating infolist: " . $e->getMessage() . "\n";
                }
            } else {
                echo "   ❌ Resource missing infolist() method\n";
            }
            
        } else {
            echo "   ❌ ViewPhoneNumber class not found\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ℹ️  No PhoneNumber records found\n";
}

echo "\n2. Testing RetellAgent View Page:\n";
$retellAgent = RetellAgent::first();
if ($retellAgent) {
    echo "   Record ID: {$retellAgent->id}\n";
    
    try {
        $viewPageClass = 'App\Filament\Admin\Resources\RetellAgentResource\Pages\ViewRetellAgent';
        
        if (class_exists($viewPageClass)) {
            echo "   ✅ ViewRetellAgent class exists\n";
            
            $page = new $viewPageClass();
            $resource = $page::getResource();
            echo "   Resource: " . $resource . "\n";
            
            if (method_exists($resource, 'infolist')) {
                echo "   ✅ Resource has infolist() method\n";
            } else {
                echo "   ❌ Resource missing infolist() method\n";
            }
        } else {
            echo "   ❌ ViewRetellAgent class not found\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ℹ️  No RetellAgent records found\n";
}

echo "\n3. Testing Tenant View Page:\n";
$tenant = Tenant::first();
if ($tenant) {
    echo "   Record ID: {$tenant->id}\n";
    
    try {
        $viewPageClass = 'App\Filament\Admin\Resources\TenantResource\Pages\ViewTenant';
        
        if (class_exists($viewPageClass)) {
            echo "   ✅ ViewTenant class exists\n";
            
            $page = new $viewPageClass();
            $resource = $page::getResource();
            echo "   Resource: " . $resource . "\n";
            
            if (method_exists($resource, 'infolist')) {
                echo "   ✅ Resource has infolist() method\n";
            } else {
                echo "   ❌ Resource missing infolist() method\n";
            }
        } else {
            echo "   ❌ ViewTenant class not found\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ℹ️  No Tenant records found\n";
}

echo "\n\nChecking for recent errors in log:\n";
echo "-----------------------------------\n";

// Check error log
$logFile = storage_path('logs/laravel.log');
$lines = file($logFile);
$lastLines = array_slice($lines, -20);

$foundError = false;
foreach ($lastLines as $line) {
    if (strpos($line, 'ERROR') !== false || strpos($line, 'Exception') !== false) {
        echo $line;
        $foundError = true;
    }
}

if (!$foundError) {
    echo "No recent errors in log file.\n";
}