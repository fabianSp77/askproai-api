<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Service;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════\n";
echo "  HERRENHAARSCHNITT SERVICE ANALYSIS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Get the branch ID from the call
$branchId = '34c4d48e-4753-4715-9c30-c55843a943e8';
$branch = Branch::find($branchId);

echo "Branch Info:\n";
echo "  ID: {$branch->id}\n";
echo "  Name: {$branch->name}\n";
echo "  Company ID: {$branch->company_id}\n\n";

// Check for services with "Herren" or "Haar" in the name
echo "Services with 'Herren' or 'Haar' in name:\n";
echo "════════════════════════════════════════════════════════════\n";

$services = Service::where('company_id', 1)
    ->where(function($query) {
        $query->where('name', 'LIKE', '%Herren%')
              ->orWhere('name', 'LIKE', '%Haar%')
              ->orWhere('slug', 'LIKE', '%herren%')
              ->orWhere('slug', 'LIKE', '%haar%');
    })
    ->get();

if ($services->isEmpty()) {
    echo "  ❌ NO SERVICES FOUND\n\n";
} else {
    foreach ($services as $service) {
        echo "  Service: {$service->name}\n";
        echo "    - ID: {$service->id}\n";
        echo "    - Slug: {$service->slug}\n";
        echo "    - Active: " . ($service->is_active ? 'YES' : 'NO') . "\n";
        echo "    - Cal.com Event Type ID: " . ($service->calcom_event_type_id ?? 'NOT SET') . "\n";
        echo "    - Branch ID: " . ($service->branch_id ?? 'NULL (all branches)') . "\n";

        // Check if this service is linked to the branch via pivot
        $linkedToBranch = DB::table('branch_service')
            ->where('service_id', $service->id)
            ->where('branch_id', $branchId)
            ->exists();

        echo "    - Linked to Branch {$branch->name}: " . ($linkedToBranch ? 'YES' : 'NO') . "\n";
        echo "\n";
    }
}

// Check ALL services for this branch
echo "ALL Services available for Branch '{$branch->name}':\n";
echo "════════════════════════════════════════════════════════════\n";

$availableServices = Service::where('company_id', 1)
    ->where('is_active', true)
    ->whereNotNull('calcom_event_type_id')
    ->where(function($query) use ($branchId) {
        $query->where('branch_id', $branchId)
              ->orWhereHas('branches', function($q) use ($branchId) {
                  $q->where('branches.id', $branchId);
              })
              ->orWhereNull('branch_id');
    })
    ->get();

if ($availableServices->isEmpty()) {
    echo "  ❌ NO SERVICES AVAILABLE FOR THIS BRANCH\n\n";
} else {
    echo "  Found " . $availableServices->count() . " services:\n\n";
    foreach ($availableServices as $service) {
        echo "  - {$service->name} (Slug: {$service->slug})\n";
        echo "    Cal.com ID: {$service->calcom_event_type_id}\n";
        echo "    Price: €{$service->price} | Duration: {$service->duration_minutes}min\n\n";
    }
}

// Check service synonyms
echo "Service Synonyms for 'Herrenhaarschnitt':\n";
echo "════════════════════════════════════════════════════════════\n";

$synonyms = DB::table('service_synonyms')
    ->join('services', 'service_synonyms.service_id', '=', 'services.id')
    ->where('services.company_id', 1)
    ->where(function($query) {
        $query->whereRaw('LOWER(service_synonyms.synonym) = LOWER(?)', ['Herrenhaarschnitt'])
              ->orWhereRaw('LOWER(service_synonyms.synonym) LIKE LOWER(?)', ['%Herrenhaarschnitt%']);
    })
    ->select('services.name as service_name', 'service_synonyms.synonym', 'service_synonyms.confidence')
    ->get();

if ($synonyms->isEmpty()) {
    echo "  ❌ NO SYNONYMS FOUND FOR 'Herrenhaarschnitt'\n\n";
} else {
    foreach ($synonyms as $synonym) {
        echo "  - Synonym: {$synonym->synonym}\n";
        echo "    → Maps to Service: {$synonym->service_name}\n";
        echo "    Confidence: {$synonym->confidence}\n\n";
    }
}

echo "═══════════════════════════════════════════════════════════\n";
echo "  ANALYSIS COMPLETE\n";
echo "═══════════════════════════════════════════════════════════\n";
