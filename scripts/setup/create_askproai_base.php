<?php

/**
 * CREATE ASKPROAI BASE COMPANY
 *
 * Creates AskProAI company with 1 branch, proper Cal.com and Retell integration
 *
 * Company: AskProAI
 * Branch: 1 (Hauptfiliale)
 * Cal.com Team: 39203
 * Retell Agent: agent_616d645570ae613e421edb98e7
 * Phone: +493083793369
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Models\Service;
use Illuminate\Support\Str;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║           CREATE ASKPROAI BASE COMPANY SETUP                ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Configuration
$companyData = [
    'name' => 'AskProAI',
    'slug' => 'askproai',
    'calcom_team_id' => 39203,
    'retell_agent_id' => 'agent_616d645570ae613e421edb98e7',
    'phone' => '+493083793369',
    'is_active' => true,
];

$branchData = [
    'name' => 'AskProAI Hauptfiliale',
    'slug' => 'askproai-hauptfiliale',
    'address' => 'Hauptstraße 1',
    'city' => 'Berlin',
    'postal_code' => '10115',
    'country' => 'DE',
    'phone_number' => '+493083793369',
];

// Step 1: Create Company
echo "📋 STEP 1: Creating AskProAI Company...\n";
try {
    $company = Company::create([
        'name' => $companyData['name'],
        'slug' => $companyData['slug'],
        'is_active' => $companyData['is_active'],
        'settings' => json_encode([
            'calcom_team_id' => $companyData['calcom_team_id'],
            'retell_agent_id' => $companyData['retell_agent_id'],
            'timezone' => 'Europe/Berlin',
            'locale' => 'de',
            'currency' => 'EUR',
        ]),
    ]);

    echo "   ✅ Company created: {$company->name} (ID: {$company->id})\n";
    echo "      Cal.com Team: {$companyData['calcom_team_id']}\n";
    echo "      Retell Agent: {$companyData['retell_agent_id']}\n\n";
} catch (\Exception $e) {
    echo "   ❌ Error creating company: {$e->getMessage()}\n";
    exit(1);
}

// Step 2: Create Branch
echo "📋 STEP 2: Creating Branch...\n";
try {
    $branchUuid = (string) Str::uuid();

    // Use DB::table() to explicitly set UUID
    DB::table('branches')->insert([
        'id' => $branchUuid,
        'company_id' => $company->id,
        'name' => $branchData['name'],
        'slug' => $branchData['slug'],
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Retrieve the created branch
    $branch = Branch::find($branchUuid);

    echo "   ✅ Branch created: {$branch->name}\n";
    echo "      UUID: {$branch->id}\n\n";
} catch (\Exception $e) {
    echo "   ❌ Error creating branch: {$e->getMessage()}\n";
    exit(1);
}

// Step 3: Skip Phone Number (testing schema doesn't have Retell phone mappings)
echo "📋 STEP 3: Skipping Phone Number Mapping (testing environment)...\n";
echo "   ⏭️  Phone numbers table is for customer phones, not Retell mappings\n";
echo "   ℹ️  Retell Agent: {$companyData['retell_agent_id']}\n";
echo "   ℹ️  Phone: {$companyData['phone']}\n\n";

$phoneNumber = (object)[
    'phone_number' => $companyData['phone'],
];

// Step 4: Create Sample Services for AskProAI
echo "📋 STEP 4: Creating Sample Services...\n";
$services = [
    [
        'name' => '15 Minuten Schnellberatung',
        'duration' => 15,
        'price' => 0.00,
        'description' => 'Kostenlose Erstberatung',
    ],
    [
        'name' => '30 Minuten Beratungsgespräch',
        'duration' => 30,
        'price' => 0.00,
        'description' => 'Ausführliches Beratungsgespräch',
    ],
    [
        'name' => '60 Minuten Intensivberatung',
        'duration' => 60,
        'price' => 0.00,
        'description' => 'Intensive Beratung zu komplexen Themen',
    ],
];

$createdServices = [];
foreach ($services as $serviceData) {
    try {
        $service = Service::create([
            'company_id' => $company->id,
            'name' => $serviceData['name'],
            'slug' => Str::slug($serviceData['name']),
            'description' => $serviceData['description'],
            'duration_minutes' => $serviceData['duration'],
            'price' => $serviceData['price'],
            'is_active' => true,
        ]);

        // Link service to branch
        DB::table('branch_service')->insert([
            'branch_id' => $branch->id,
            'service_id' => $service->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $createdServices[] = $service;
        echo "   ✅ Service: {$service->name} ({$service->duration_minutes} min)\n";
    } catch (\Exception $e) {
        echo "   ⚠️  Warning: Could not create service '{$serviceData['name']}': {$e->getMessage()}\n";
    }
}

echo "\n";

// Summary
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    SETUP COMPLETE                            ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "📊 ASKPROAI BASE COMPANY CREATED:\n\n";
echo "Company Details:\n";
echo "  Name:           {$company->name}\n";
echo "  ID:             {$company->id}\n";
echo "  Slug:           {$company->slug}\n";
echo "  Cal.com Team:   {$companyData['calcom_team_id']}\n";
echo "  Retell Agent:   {$companyData['retell_agent_id']}\n\n";

echo "Branch Details:\n";
echo "  Name:           {$branch->name}\n";
echo "  UUID:           {$branch->id}\n\n";

echo "Phone Number:\n";
echo "  Number:         {$phoneNumber->phone_number}\n";
echo "  Agent URL:      https://dashboard.retellai.com/agents/{$companyData['retell_agent_id']}\n\n";

echo "Services Created: " . count($createdServices) . "\n\n";

echo "🧪 NEXT STEPS:\n";
echo "  1. Test Call:    {$phoneNumber->phone_number}\n";
echo "  2. Expected:     \"Guten Tag bei AskProAI...\"\n";
echo "  3. Verify:       Cal.com Team 39203 accessible\n";
echo "  4. Admin Panel:  https://api.askproai.de/admin/companies/{$company->id}\n\n";

echo "✅ AskProAI Base Setup Complete!\n";
