<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Company;

echo "UPDATING RETELL API KEY TO WORKING VERSION\n";
echo str_repeat('=', 50) . "\n\n";

$workingApiKey = 'key_6ff998ba48e842092e04a5455d19';

// Update AskProAI company
$company = Company::find(1);
if ($company) {
    $oldKey = $company->retell_api_key ? decrypt($company->retell_api_key) : null;
    
    echo "Company: {$company->name}\n";
    echo "Old API Key: " . ($oldKey ? substr($oldKey, 0, 20) . '...' : 'Not set') . "\n";
    
    // Update with working key
    $company->retell_api_key = encrypt($workingApiKey);
    $company->save();
    
    echo "New API Key: " . substr($workingApiKey, 0, 20) . "...\n";
    echo "✅ API Key updated successfully!\n";
} else {
    echo "❌ Company not found\n";
}

// Clear config cache
echo "\nClearing config cache...\n";
exec('php artisan config:clear');
echo "✅ Config cache cleared\n";

echo "\n✅ DONE! The working API key is now active.\n";