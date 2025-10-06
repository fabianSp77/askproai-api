<?php

use App\Models\Service;
use App\Models\Company;

echo "\n=========================================\n";
echo " Testing Services Loading & Filtering\n";
echo " " . date('Y-m-d H:i:s') . "\n";
echo "=========================================\n";

// Test 1: Basic service loading
echo "\n=== Test 1: Basic Service Loading ===\n";
$totalServices = Service::count();
$activeServices = Service::where('is_active', true)->count();
$syncedServices = Service::whereNotNull('calcom_event_type_id')->count();

echo "Total services: $totalServices\n";
echo "Active services: $activeServices\n";
echo "Synced with Cal.com: $syncedServices\n";

// Test 2: Company grouping
echo "\n=== Test 2: Services by Company ===\n";
$servicesByCompany = Service::select('company_id', DB::raw('COUNT(*) as service_count'))
    ->with('company:id,name')
    ->groupBy('company_id')
    ->get();

foreach ($servicesByCompany as $group) {
    $companyName = $group->company ? $group->company->name : 'No Company';
    echo "- $companyName: {$group->service_count} services\n";
}

// Test 3: Test filtering by company
echo "\n=== Test 3: Company Filter Test ===\n";
$companies = Company::has('services')->get();

foreach ($companies as $company) {
    $services = Service::where('company_id', $company->id)->count();
    $activeServices = Service::where('company_id', $company->id)
        ->where('is_active', true)
        ->count();
    $syncedServices = Service::where('company_id', $company->id)
        ->whereNotNull('calcom_event_type_id')
        ->count();

    echo "Company: {$company->name}\n";
    echo "  - Total: $services\n";
    echo "  - Active: $activeServices\n";
    echo "  - Synced: $syncedServices\n";
}

// Test 4: Service details
echo "\n=== Test 4: Sample Service Details ===\n";
$sampleServices = Service::with(['company', 'branch'])
    ->limit(5)
    ->get();

foreach ($sampleServices as $service) {
    echo "\nService: {$service->name}\n";
    echo "  Company: " . ($service->company ? $service->company->name : 'None') . "\n";
    echo "  Branch: " . ($service->branch ? $service->branch->name : 'None') . "\n";
    echo "  Duration: {$service->duration_minutes} minutes\n";
    echo "  Price: " . ($service->price ?? 'Not set') . " EUR\n";
    echo "  Active: " . ($service->is_active ? 'Yes' : 'No') . "\n";
    echo "  Online: " . ($service->is_online ? 'Yes' : 'No') . "\n";
    echo "  Synced: " . ($service->calcom_event_type_id ? 'Yes' : 'No') . "\n";
}

// Test 5: Service query as Filament would
echo "\n=== Test 5: Filament Query Simulation ===\n";
$filamentQuery = Service::with(['company:id,name', 'branch:id,name'])
    ->withCount([
        'appointments as total_appointments',
        'appointments as upcoming_appointments' => function ($q) {
            $q->where('starts_at', '>=', now());
        }
    ])
    ->limit(10)
    ->get();

echo "Services loaded by Filament: " . $filamentQuery->count() . "\n";

foreach ($filamentQuery->take(3) as $service) {
    echo "- {$service->name} ";
    echo "(" . ($service->company ? $service->company->name : 'No Company') . ") ";
    echo "[Appointments: {$service->total_appointments}, ";
    echo "Upcoming: {$service->upcoming_appointments}]\n";
}

echo "\n✅ All tests completed successfully!\n";