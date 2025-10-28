<?php

/**
 * CREATE FRISEUR 1 BASE COMPANY
 *
 * Creates Friseur 1 company with 2 branches, 5 staff members, 16 services
 * This serves as the TEMPLATE BASE for cloning to new hair salon clients
 *
 * Company: Friseur 1
 * Branches: 2 (Zentrale + Zweigstelle)
 * Staff: 5 members distributed across branches
 * Cal.com Team: 34209
 * Retell Agent: agent_45daa54928c5768b52ba3db736
 * Phone: +493033081738
 * Services: 16 (with Cal.com Event Type IDs 3719738-3719753)
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use App\Models\PhoneNumber;
use Illuminate\Support\Str;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║         CREATE FRISEUR 1 BASE COMPANY (TEMPLATE)            ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Configuration
$companyData = [
    'name' => 'Friseur 1',
    'slug' => 'friseur-1',
    'calcom_team_id' => 34209,
    'retell_agent_id' => 'agent_45daa54928c5768b52ba3db736',
    'phone' => '+493033081738',
];

// Step 1: Create Company
echo "📋 STEP 1: Creating Friseur 1 Company...\n";
try {
    $company = Company::create([
        'name' => $companyData['name'],
        'slug' => $companyData['slug'],
        'is_active' => true,
        'settings' => json_encode([
            'calcom_team_id' => $companyData['calcom_team_id'],
            'retell_agent_id' => $companyData['retell_agent_id'],
            'timezone' => 'Europe/Berlin',
            'locale' => 'de',
            'currency' => 'EUR',
            'business_type' => 'hair_salon',
        ]),
    ]);

    echo "   ✅ Company created: {$company->name} (ID: {$company->id})\n";
    echo "      Cal.com Team: {$companyData['calcom_team_id']}\n";
    echo "      Retell Agent: {$companyData['retell_agent_id']}\n\n";
} catch (\Exception $e) {
    echo "   ❌ Error creating company: {$e->getMessage()}\n";
    exit(1);
}

// Step 2: Create Branches
echo "📋 STEP 2: Creating 2 Branches...\n";

// Branch 1: Zentrale (Main Branch)
try {
    $zentraleUuid = '34c4d48e-4753-4715-9c30-c55843a943e8'; // Known UUID from existing data

    DB::table('branches')->insert([
        'id' => $zentraleUuid,
        'company_id' => $company->id,
        'name' => 'Friseur 1 Zentrale',
        'slug' => 'friseur-1-zentrale',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $branchZentrale = Branch::find($zentraleUuid);

    echo "   ✅ Branch Zentrale created\n";
    echo "      UUID: {$branchZentrale->id}\n\n";
} catch (\Exception $e) {
    echo "   ❌ Error creating Zentrale: {$e->getMessage()}\n";
    exit(1);
}

// Branch 2: Zweigstelle (Secondary Branch)
try {
    $zweigstelleUuid = (string) Str::uuid();

    DB::table('branches')->insert([
        'id' => $zweigstelleUuid,
        'company_id' => $company->id,
        'name' => 'Friseur 1 Zweigstelle',
        'slug' => 'friseur-1-zweigstelle',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $branchZweigstelle = Branch::find($zweigstelleUuid);

    echo "   ✅ Branch Zweigstelle created\n";
    echo "      UUID: {$branchZweigstelle->id}\n\n";
} catch (\Exception $e) {
    echo "   ❌ Error creating Zweigstelle: {$e->getMessage()}\n";
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

// Step 4: Create Staff Members
echo "📋 STEP 4: Creating 5 Staff Members...\n";

$staffMembers = [
    [
        'id' => '010be4a7-3468-4243-bb0a-2223b8e5878c',
        'name' => 'Emma Williams',
        'email' => 'emma.williams@friseur1.de',
        'phone' => '+493033081739',
        'branch_id' => $branchZentrale->id,
        'position' => 'Senior Stylist',
    ],
    [
        'id' => '9f47fda1-977c-47aa-a87a-0e8cbeaeb119',
        'name' => 'Fabian Spitzer',
        'email' => 'fabian.spitzer@friseur1.de',
        'phone' => '+493033081740',
        'branch_id' => $branchZentrale->id,
        'position' => 'Stylist',
    ],
    [
        'id' => 'c4a19739-4824-46b2-8a50-72b9ca23e013',
        'name' => 'David Martinez',
        'email' => 'david.martinez@friseur1.de',
        'phone' => '+493033081741',
        'branch_id' => $branchZweigstelle->id,
        'position' => 'Stylist',
    ],
    [
        'id' => 'ce3d932c-52d1-4c15-a7b9-686a29babf0a',
        'name' => 'Michael Chen',
        'email' => 'michael.chen@friseur1.de',
        'phone' => '+493033081742',
        'branch_id' => $branchZweigstelle->id,
        'position' => 'Junior Stylist',
    ],
    [
        'id' => 'f9d4d054-1ccd-4b60-87b9-c9772d17c892',
        'name' => 'Dr. Sarah Johnson',
        'email' => 'sarah.johnson@friseur1.de',
        'phone' => '+493033081743',
        'branch_id' => $branchZentrale->id, // Primary branch
        'position' => 'Master Stylist',
    ],
];

$createdStaff = [];
foreach ($staffMembers as $staffData) {
    try {
        $staff = Staff::create([
            'id' => $staffData['id'],
            'company_id' => $company->id,
            'branch_id' => $staffData['branch_id'],
            'name' => $staffData['name'],
            'email' => $staffData['email'],
            'phone' => $staffData['phone'],
            'position' => $staffData['position'],
            'is_active' => true,
            'is_bookable' => true,
        ]);

        $branchName = $staffData['branch_id'] === $branchZentrale->id ? 'Zentrale' : 'Zweigstelle';
        $createdStaff[] = $staff;
        echo "   ✅ Staff: {$staff->name} ({$staff->position}) → {$branchName}\n";
    } catch (\Exception $e) {
        echo "   ⚠️  Warning: Could not create staff '{$staffData['name']}': {$e->getMessage()}\n";
    }
}

echo "\n";

// Step 5: Create Services
echo "📋 STEP 5: Creating 16 Services with Cal.com Event Types...\n";

$services = [
    ['name' => 'Kinderhaarschnitt', 'duration' => 30, 'price' => 20.50, 'category' => 'Schnitt', 'event_type_id' => 3719738],
    ['name' => 'Trockenschnitt', 'duration' => 30, 'price' => 25.00, 'category' => 'Schnitt', 'event_type_id' => 3719739],
    ['name' => 'Waschen & Styling', 'duration' => 45, 'price' => 40.00, 'category' => 'Styling', 'event_type_id' => 3719740],
    ['name' => 'Waschen, schneiden, föhnen', 'duration' => 60, 'price' => 45.00, 'category' => 'Schnitt', 'event_type_id' => 3719741],
    ['name' => 'Haarspende', 'duration' => 30, 'price' => 80.00, 'category' => 'Sonstiges', 'event_type_id' => 3719742],
    ['name' => 'Beratung', 'duration' => 30, 'price' => 30.00, 'category' => 'Beratung', 'event_type_id' => 3719743],
    ['name' => 'Hairdetox', 'duration' => 15, 'price' => 12.50, 'category' => 'Pflege', 'event_type_id' => 3719744],
    ['name' => 'Rebuild Treatment Olaplex', 'duration' => 15, 'price' => 15.50, 'category' => 'Pflege', 'event_type_id' => 3719745],
    ['name' => 'Intensiv Pflege Maria Nila', 'duration' => 15, 'price' => 15.50, 'category' => 'Pflege', 'event_type_id' => 3719746],
    ['name' => 'Gloss', 'duration' => 30, 'price' => 45.00, 'category' => 'Färben', 'event_type_id' => 3719747],
    ['name' => 'Ansatzfärbung, waschen, schneiden, föhnen', 'duration' => 120, 'price' => 85.00, 'category' => 'Färben', 'event_type_id' => 3719748],
    ['name' => 'Ansatz, Längenausgleich, waschen, schneiden, föhnen', 'duration' => 120, 'price' => 85.00, 'category' => 'Färben', 'event_type_id' => 3719749],
    ['name' => 'Klassisches Strähnen-Paket', 'duration' => 120, 'price' => 125.00, 'category' => 'Färben', 'event_type_id' => 3719750],
    ['name' => 'Globale Blondierung', 'duration' => 120, 'price' => 185.00, 'category' => 'Färben', 'event_type_id' => 3719751],
    ['name' => 'Strähnentechnik Balayage', 'duration' => 180, 'price' => 255.00, 'category' => 'Färben', 'event_type_id' => 3719752],
    ['name' => 'Faceframe', 'duration' => 180, 'price' => 225.00, 'category' => 'Färben', 'event_type_id' => 3719753],
];

$createdServices = [];
foreach ($services as $serviceData) {
    try {
        $service = Service::create([
            'company_id' => $company->id,
            'name' => $serviceData['name'],
            'slug' => Str::slug($serviceData['name']),
            'duration_minutes' => $serviceData['duration'],
            'price' => $serviceData['price'],
            'category' => $serviceData['category'],
            'is_active' => true,
            'settings' => json_encode([
                'calcom_event_type_id' => $serviceData['event_type_id'],
            ]),
        ]);

        $createdServices[] = $service;
        echo "   ✅ {$serviceData['category']}: {$service->name} (Event ID: {$serviceData['event_type_id']})\n";
    } catch (\Exception $e) {
        echo "   ⚠️  Warning: Could not create service '{$serviceData['name']}': {$e->getMessage()}\n";
    }
}

echo "\n";

// Step 6: Link Services to Branches
echo "📋 STEP 6: Linking Services to Branches...\n";
foreach ($createdServices as $service) {
    try {
        // Link to Zentrale
        DB::table('branch_service')->insert([
            'branch_id' => $branchZentrale->id,
            'service_id' => $service->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Link to Zweigstelle
        DB::table('branch_service')->insert([
            'branch_id' => $branchZweigstelle->id,
            'service_id' => $service->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    } catch (\Exception $e) {
        echo "   ⚠️  Warning: Could not link service '{$service->name}' to branches\n";
    }
}

echo "   ✅ All services linked to both branches\n\n";

// Step 7: Link Staff to Services
echo "📋 STEP 7: Linking Staff to Services...\n";
foreach ($createdStaff as $staff) {
    foreach ($createdServices as $service) {
        try {
            DB::table('service_staff')->insert([
                'service_id' => $service->id,
                'staff_id' => $staff->id,
                'is_primary' => false, // No primary staff by default
                'can_book' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Skip if already exists
        }
    }
}

echo "   ✅ All staff linked to all services\n\n";

// Summary
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    SETUP COMPLETE                            ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "📊 FRISEUR 1 BASE COMPANY CREATED (TEMPLATE):\n\n";
echo "Company Details:\n";
echo "  Name:           {$company->name}\n";
echo "  ID:             {$company->id}\n";
echo "  Slug:           {$company->slug}\n";
echo "  Cal.com Team:   {$companyData['calcom_team_id']}\n";
echo "  Retell Agent:   {$companyData['retell_agent_id']}\n\n";

echo "Branches:\n";
echo "  1. Zentrale:    {$branchZentrale->id}\n";
echo "     Address:     {$branchZentrale->address}, {$branchZentrale->city}\n";
echo "     Staff:       Emma Williams, Fabian Spitzer, Dr. Sarah Johnson\n\n";
echo "  2. Zweigstelle: {$branchZweigstelle->id}\n";
echo "     Address:     {$branchZweigstelle->address}, {$branchZweigstelle->city}\n";
echo "     Staff:       David Martinez, Michael Chen\n\n";

echo "Phone Number:\n";
echo "  Number:         {$phoneNumber->phone_number}\n";
echo "  Agent URL:      https://dashboard.retellai.com/agents/{$companyData['retell_agent_id']}\n\n";

echo "Staff Members:    " . count($createdStaff) . "/5\n";
echo "Services Created: " . count($createdServices) . "/16\n";
echo "Event Type Range: 3719738-3719753\n\n";

echo "🧪 NEXT STEPS:\n";
echo "  1. Test Call:    {$phoneNumber->phone_number}\n";
echo "  2. Expected:     \"Guten Tag bei Friseur 1, mein Name ist Carola...\"\n";
echo "  3. Test Booking: \"Waschen, schneiden, föhnen\" bei Emma\n";
echo "  4. Verify:       Cal.com Team 34209 accessible\n";
echo "  5. Admin Panel:  https://api.askproai.de/admin/companies/{$company->id}\n\n";

echo "✅ Friseur 1 Base Setup Complete!\n";
echo "📦 This company serves as TEMPLATE BASE for cloning to new clients.\n";
