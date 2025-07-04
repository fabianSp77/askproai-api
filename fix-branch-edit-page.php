<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\Company;

echo "🔧 FIXING BRANCH CONFIGURATION AND PROVIDING LINKS\n";
echo str_repeat("=", 60) . "\n\n";

// 1. Update Branch Model to fix configuration progress
$branch = Branch::withoutGlobalScopes()->where('id', 1)->first();
$company = Company::withoutGlobalScopes()->first();

if (!$branch) {
    echo "❌ No branch found! Creating a default branch...\n";
    
    if (!$company) {
        echo "❌ No company found! Please run the setup first.\n";
        exit(1);
    }
    
    $branch = Branch::create([
        'company_id' => $company->id,
        'name' => 'Hauptfiliale',
        'address' => 'Musterstraße 1',
        'city' => 'Berlin',
        'postal_code' => '12345',
        'country' => 'Deutschland',
        'phone_number' => $company->phone_number ?? '+49 30 12345678',
        'notification_email' => $company->email ?? 'info@example.com',
        'active' => true,
        'calendar_mode' => 'inherit',
    ]);
    
    echo "✅ Created new branch: " . $branch->name . "\n";
}

// 2. Fix Cal.com configuration
echo "\n📅 CAL.COM CONFIGURATION:\n";
echo str_repeat("-", 40) . "\n";

// Update company with Cal.com API key from .env
if ($company && !$company->calcom_api_key) {
    $company->calcom_api_key = env('CALCOM_API_KEY', 'cal_live_bd7aedbdf12085c5312c79ba73585920');
    $company->calcom_event_type_id = env('CALCOM_EVENT_TYPE_ID', 2026979);
    $company->save();
    echo "✅ Updated company with Cal.com credentials\n";
} else {
    echo "✅ Company already has Cal.com credentials\n";
}

// 3. Fix configuration progress calculation
if ($branch) {
    // Update branch to ensure all required fields are set
    $updates = [];
    
    if (empty($branch->notification_email) && $company) {
        $updates['notification_email'] = $company->email ?? 'info@example.com';
    }
    
    if (empty($branch->phone_number) && $company) {
        $updates['phone_number'] = $company->phone_number ?? '+49 30 12345678';
    }
    
    if (!empty($updates)) {
        $branch->update($updates);
        echo "✅ Updated branch with missing fields\n";
    }
    
    // Calculate configuration progress
    $progress = $branch->configuration_progress;
    echo "\n📊 Configuration Progress: " . $progress['percentage'] . "%\n";
    echo "Steps completed:\n";
    foreach ($progress['steps'] as $step => $completed) {
        echo "  - " . ucfirst(str_replace('_', ' ', $step)) . ": " . ($completed ? '✅' : '❌') . "\n";
    }
}

// 4. Generate direct links
echo "\n🔗 DIRECT ADMIN LINKS:\n";
echo str_repeat("-", 40) . "\n";

$baseUrl = "https://api.askproai.de/admin";

echo "1. Company Configuration:\n";
echo "   $baseUrl/companies/" . $company->id . "/edit\n\n";

echo "2. Branch List:\n";
echo "   $baseUrl/branches\n\n";

if ($branch) {
    echo "3. Edit Branch (ID: " . $branch->id . "):\n";
    echo "   $baseUrl/branches/" . $branch->id . "/edit\n\n";
}

echo "4. Basic Company Config Page:\n";
echo "   $baseUrl/basic-company-config\n\n";

echo "5. Retell Control Center:\n";
echo "   $baseUrl/retell-ultimate-control-center\n\n";

// 5. Test if configuration is working
echo "\n🧪 TESTING CONFIGURATION:\n";
echo str_repeat("-", 40) . "\n";

// Test Cal.com API
$apiKey = $company->calcom_api_key;
if ($apiKey) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.cal.com/v1/me");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "✅ Cal.com API Key is valid!\n";
        $data = json_decode($response, true);
        echo "   Connected as: " . ($data['username'] ?? 'Unknown') . "\n";
    } else {
        echo "❌ Cal.com API Key invalid (HTTP $httpCode)\n";
    }
} else {
    echo "❌ No Cal.com API Key configured\n";
}

echo "\n✅ SETUP COMPLETE!\n";
echo "You can now access the branch configuration page.\n";