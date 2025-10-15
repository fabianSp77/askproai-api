<?php

/**
 * Phase 3: Krückenberg Friseur-Setup
 *
 * Configures Krückenberg Servicegruppe as a professional hair salon chain
 * - 2 Filialen (branches)
 * - 17 Friseur-Services
 *
 * Run: php database/scripts/setup_kruckenberg_friseur.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   KRÜCKENBERG FRISEUR-SETUP - PHASE 3\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$company = Company::find(1);

if (!$company) {
    echo "❌ ERROR: Company ID 1 (Krückenberg) not found!\n\n";
    exit(1);
}

echo "🏢 Company: {$company->name}\n\n";

DB::beginTransaction();

try {
    // Step 1: Clean up dummy branches (keep only "Zentrale")
    echo "🧹 STEP 1: Cleaning up dummy branches...\n";

    $dummyBranches = Branch::where('company_id', 1)
        ->where('name', '!=', 'Krückeberg Servicegruppe Zentrale')
        ->get();

    $deletedBranches = 0;
    foreach ($dummyBranches as $branch) {
        echo "   Deleting: {$branch->name}\n";
        $branch->delete();
        $deletedBranches++;
    }

    echo "   ✅ Deleted {$deletedBranches} dummy branches\n\n";

    // Step 2: Clean up old services (remove Cal.com sync first)
    echo "🧹 STEP 2: Cleaning up old services...\n";

    $oldServices = Service::where('company_id', 1)->get();
    $deletedServices = 0;
    foreach ($oldServices as $service) {
        echo "   Processing: {$service->name}\n";

        // Remove Cal.com sync to allow deletion
        $service->calcom_event_type_id = null;
        $service->last_calcom_sync = null;
        $service->save();

        // Now delete
        $service->delete();
        $deletedServices++;
    }

    echo "   ✅ Deleted {$deletedServices} old services\n\n";

    // Step 3: Create 2 real Friseur branches
    echo "📍 STEP 3: Creating 2 Friseur branches...\n";

    $branches = [
        [
            'name' => 'Krückenberg Friseur - Innenstadt',
            'address' => 'Oppelner Straße 16, 14129 Berlin',
            'phone_number' => '+49 30 12345678',
            'notification_email' => 'innenstadt@krueckenberg-friseur.de',
            'is_active' => true,
        ],
        [
            'name' => 'Krückenberg Friseur - Charlottenburg',
            'address' => 'Kurfürstendamm 45, 10707 Berlin',
            'phone_number' => '+49 30 87654321',
            'notification_email' => 'charlottenburg@krueckenberg-friseur.de',
            'is_active' => true,
        ],
    ];

    $createdBranches = [];
    foreach ($branches as $branchData) {
        $branchId = Str::uuid()->toString();

        DB::table('branches')->insert([
            'id' => $branchId,
            'company_id' => 1,
            'name' => $branchData['name'],
            'address' => $branchData['address'],
            'phone_number' => $branchData['phone_number'],
            'notification_email' => $branchData['notification_email'],
            'is_active' => $branchData['is_active'],
            'active' => $branchData['is_active'], // also set 'active' column
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $branch = Branch::find($branchId);
        $createdBranches[] = $branch;
        echo "   ✅ Created: {$branch->name}\n";
    }

    echo "\n";

    // Step 4: Create 17 Friseur services
    echo "✂️  STEP 4: Creating 17 Friseur services...\n";

    $services = [
        // Herrenhaarschnitte
        ['name' => 'Herrenhaarschnitt Classic', 'duration' => 30, 'price' => 28.00, 'category' => 'herren'],
        ['name' => 'Herrenhaarschnitt Premium', 'duration' => 45, 'price' => 38.00, 'category' => 'herren'],
        ['name' => 'Herrenhaarschnitt + Bart', 'duration' => 60, 'price' => 48.00, 'category' => 'herren'],
        ['name' => 'Herrenhaarschnitt + Waschen', 'duration' => 40, 'price' => 35.00, 'category' => 'herren'],

        // Damenhaarschnitte
        ['name' => 'Damenhaarschnitt Kurz', 'duration' => 45, 'price' => 42.00, 'category' => 'damen'],
        ['name' => 'Damenhaarschnitt Mittel', 'duration' => 60, 'price' => 52.00, 'category' => 'damen'],
        ['name' => 'Damenhaarschnitt Lang', 'duration' => 75, 'price' => 65.00, 'category' => 'damen'],
        ['name' => 'Damenhaarschnitt + Föhnen', 'duration' => 90, 'price' => 75.00, 'category' => 'damen'],

        // Färben & Strähnchen
        ['name' => 'Färben Kurzhaar', 'duration' => 90, 'price' => 65.00, 'category' => 'farbe'],
        ['name' => 'Färben Langhaar', 'duration' => 120, 'price' => 95.00, 'category' => 'farbe'],
        ['name' => 'Strähnchen Partial', 'duration' => 120, 'price' => 85.00, 'category' => 'farbe'],
        ['name' => 'Strähnchen Komplett', 'duration' => 150, 'price' => 120.00, 'category' => 'farbe'],

        // Spezialbehandlungen
        ['name' => 'Dauerwelle', 'duration' => 120, 'price' => 85.00, 'category' => 'special'],
        ['name' => 'Keratin-Behandlung', 'duration' => 180, 'price' => 150.00, 'category' => 'special'],
        ['name' => 'Hochsteckfrisur', 'duration' => 90, 'price' => 75.00, 'category' => 'special'],

        // Kinder & Basic
        ['name' => 'Kinderhaarschnitt (bis 12 Jahre)', 'duration' => 30, 'price' => 18.00, 'category' => 'kinder'],
        ['name' => 'Waschen & Föhnen', 'duration' => 30, 'price' => 25.00, 'category' => 'basic'],
    ];

    $createdServices = [];
    foreach ($services as $serviceData) {
        $serviceId = DB::table('services')->insertGetId([
            'company_id' => 1,
            'name' => $serviceData['name'],
            'duration' => $serviceData['duration'],
            'price' => $serviceData['price'],
            'description' => 'Professioneller Friseurservice bei Krückenberg',
            'is_active' => true,
            'category' => $serviceData['category'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = Service::find($serviceId);
        $createdServices[] = $service;
        echo sprintf("   ✅ %-40s (€%6.2f, %3d min)\n", $service->name, $service->price, $service->duration);
    }

    echo "\n";

    // Step 5: Assign services to both branches
    echo "🔗 STEP 5: Assigning services to branches...\n";

    foreach ($createdBranches as $branch) {
        foreach ($createdServices as $service) {
            DB::table('branch_service')->insert([
                'branch_id' => $branch->id,
                'service_id' => $service->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        echo "   ✅ Assigned " . count($createdServices) . " services to: {$branch->name}\n";
    }

    echo "\n";

    DB::commit();

    echo "═══════════════════════════════════════════════════════════════\n";
    echo "   ✅ KRÜCKENBERG FRISEUR-SETUP COMPLETE\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    echo "📊 Summary:\n";
    echo "   Company: Krückenberg Servicegruppe (ID: 1)\n";
    echo "   Branches: " . Branch::where('company_id', 1)->count() . " active branches\n";
    echo "   Services: " . Service::where('company_id', 1)->count() . " friseur services\n";
    echo "   Assignments: " . (count($createdBranches) * count($createdServices)) . " service-branch links\n";

    echo "\n";
    echo "📍 Branches:\n";
    foreach ($createdBranches as $branch) {
        echo "   - {$branch->name}\n";
        echo "     Address: {$branch->address}\n";
        echo "     Phone: {$branch->phone}\n";
        echo "     Services: " . count($createdServices) . "\n";
    }

    echo "\n";
    echo "✂️  Service Categories:\n";
    echo "   Herren: 4 services (€28-48, 30-60 min)\n";
    echo "   Damen: 4 services (€42-75, 45-90 min)\n";
    echo "   Färben: 4 services (€65-120, 90-150 min)\n";
    echo "   Special: 3 services (€75-150, 90-180 min)\n";
    echo "   Kinder: 1 service (€18, 30 min)\n";
    echo "   Basic: 1 service (€25, 30 min)\n";

    echo "\n";
    echo "✅ Ready for appointment booking via Retell AI!\n";
    echo "\n";

    Log::info('Phase 3: Krückenberg Friseur-Setup completed', [
        'branches' => count($createdBranches),
        'services' => count($createdServices),
        'deleted_dummy_branches' => $deletedBranches,
        'deleted_old_services' => $deletedServices,
    ]);

} catch (\Exception $e) {
    DB::rollBack();

    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "   ❌ ERROR\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n";
    echo "⚠️  Transaction rolled back. No changes were made.\n";
    echo "\n";

    Log::error('Phase 3: Krückenberg Friseur-Setup failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    exit(1);
}
