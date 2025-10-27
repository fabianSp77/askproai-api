<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║         SERVICE INTEGRITY CHECK REPORT               ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

// Check 1: Team ID mismatches
echo "1️⃣ TEAM ID MISMATCHES:\n";

$services = App\Models\Service::with(['company'])
    ->whereNotNull('calcom_event_type_id')
    ->get();

$mismatches = [];
foreach ($services as $service) {
    if (!$service->company) continue;

    $mapping = DB::table('calcom_event_mappings')
        ->where('calcom_event_type_id', $service->calcom_event_type_id)
        ->first();

    if ($mapping && $service->company->calcom_team_id) {
        $mappingTeam = $mapping->calcom_team_id;
        $companyTeam = $service->company->calcom_team_id;

        if (!$mappingTeam || $mappingTeam != $companyTeam) {
            $mismatches[] = [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'company_name' => $service->company->name,
                'company_team_id' => $companyTeam,
                'mapping_team_id' => $mappingTeam
            ];
        }
    }
}

echo "   Count: " . count($mismatches) . "\n";
if (count($mismatches) > 0) {
    foreach ($mismatches as $m) {
        echo "   ⚠️ Service {$m['service_id']}: {$m['service_name']}\n";
        echo "      Company Team: {$m['company_team_id']} | Mapping Team: " . ($m['mapping_team_id'] ?? 'NULL') . "\n";
    }
} else {
    echo "   ✅ All team IDs are consistent!\n";
}
echo "\n";

// Check 2: Duplicate Event Type IDs
echo "2️⃣ DUPLICATE EVENT TYPE IDS:\n";
$duplicates = App\Models\Service::select('calcom_event_type_id')
    ->whereNotNull('calcom_event_type_id')
    ->groupBy('calcom_event_type_id')
    ->havingRaw('COUNT(*) > 1')
    ->pluck('calcom_event_type_id');

echo "   Count: " . $duplicates->count() . "\n";
if ($duplicates->count() > 0) {
    foreach ($duplicates as $eventTypeId) {
        $servicesWithDup = App\Models\Service::where('calcom_event_type_id', $eventTypeId)->get();
        echo "   ⚠️ Event Type {$eventTypeId}: {$servicesWithDup->count()} services\n";
        foreach ($servicesWithDup as $s) {
            echo "      - Service {$s->id}: {$s->name} ({$s->company->name})\n";
        }
    }
} else {
    echo "   ✅ No duplicate Event Type IDs!\n";
}
echo "\n";

// Check 3: Sync status inconsistencies
echo "3️⃣ SYNC STATUS ISSUES:\n";
$syncIssues = App\Models\Service::whereNotNull('calcom_event_type_id')
    ->where('sync_status', '!=', 'synced')
    ->get();

echo "   Count: " . $syncIssues->count() . "\n";
if ($syncIssues->count() > 0) {
    foreach ($syncIssues as $service) {
        echo "   ⚠️ Service {$service->id}: {$service->name}\n";
        echo "      Status: {$service->sync_status} (should be synced)\n";
    }
} else {
    echo "   ✅ All sync statuses are correct!\n";
}
echo "\n";

// Check 4: Overall summary
echo "4️⃣ SUMMARY:\n";
$totalServices = App\Models\Service::count();
$withCalcom = App\Models\Service::whereNotNull('calcom_event_type_id')->count();
$totalMappings = DB::table('calcom_event_mappings')->count();
$totalCompanies = App\Models\Company::count();
$companiesWithTeam = App\Models\Company::whereNotNull('calcom_team_id')->count();

echo "   Total Services: $totalServices\n";
echo "   Services with Cal.com: $withCalcom\n";
echo "   Total Event Mappings: $totalMappings\n";
echo "   Total Companies: $totalCompanies\n";
echo "   Companies with Team ID: $companiesWithTeam\n";
echo "\n";

// Final health score
$issues = count($mismatches) + $duplicates->count() + $syncIssues->count();
if ($issues === 0) {
    echo "╔══════════════════════════════════════════════════════╗\n";
    echo "║          ✅ SYSTEM IS 100% HEALTHY! ✅              ║\n";
    echo "╚══════════════════════════════════════════════════════╝\n";
} else {
    echo "⚠️ Found $issues issues that need attention\n";
}
