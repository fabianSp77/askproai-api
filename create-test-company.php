<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use App\Models\Company;
use App\Models\Branch;
use Illuminate\Support\Str;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Create a test company without Cal.com configuration
    $company2 = Company::create([
        'name' => 'Test Friseur Salon',
        'email' => 'info@test-friseur.de',
        'phone' => '+49 30 12345678',
        'is_active' => true,
        'settings' => [
            'timezone' => 'Europe/Berlin',
            'language' => 'de',
        ]
    ]);
    
    echo "✅ Created Company: {$company2->name} (ID: {$company2->id})\n";
    
    // Create a branch for this company
    $branch = Branch::create([
        'id' => Str::uuid(),
        'company_id' => $company2->id,
        'name' => 'Hauptfiliale Test',
        'is_active' => true,
        'is_main' => true,
    ]);
    
    echo "✅ Created Branch: {$branch->name}\n";
    
    // Create another test company WITH Cal.com configuration
    $company3 = Company::create([
        'name' => 'Premium Beauty Center',
        'email' => 'info@premium-beauty.de',
        'phone' => '+49 30 98765432',
        'is_active' => true,
        'calcom_api_key' => encrypt('cal_test_key_12345'), // Fake key for testing
        'calcom_team_id' => null, // No team ID yet
        'settings' => [
            'timezone' => 'Europe/Berlin',
            'language' => 'de',
        ]
    ]);
    
    echo "✅ Created Company: {$company3->name} (ID: {$company3->id}) - With fake Cal.com key\n";
    
    // Create a branch for this company
    $branch3 = Branch::create([
        'id' => Str::uuid(),
        'company_id' => $company3->id,
        'name' => 'Beauty Center Mitte',
        'is_active' => true,
        'is_main' => true,
    ]);
    
    echo "✅ Created Branch: {$branch3->name}\n";
    
    echo "\n📋 Summary:\n";
    echo "- Total companies: " . Company::count() . "\n";
    echo "- Companies with Cal.com API: " . Company::whereNotNull('calcom_api_key')->count() . "\n";
    echo "- Companies with Team ID: " . Company::whereNotNull('calcom_team_id')->count() . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}