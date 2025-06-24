#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\EventTypeMatchingService;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n=== Debug EventTypeMatchingService ===\n\n";

try {
    // Get company and branch without tenant scope
    $company = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)->first();
    $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)->first();
    
    // Set company context for tenant scope
    app()->instance('current_company_id', $company->id);
    
    echo "Company: {$company->name} (ID: {$company->id})\n";
    echo "Branch: {$branch->name} (ID: {$branch->id})\n\n";
    
    // Test 1: Check services directly
    echo "=== Direct Service Query ===\n";
    $services = Service::where('company_id', $branch->company_id)
        ->where(function ($query) use ($branch) {
            $query->where('branch_id', $branch->id)
                  ->orWhereNull('branch_id');
        })
        ->where('is_active', true)
        ->get();
    
    echo "Found {$services->count()} services:\n";
    foreach ($services as $service) {
        echo "- {$service->name} (ID: {$service->id}, Branch: " . ($service->branch_id ?? 'NULL') . ")\n";
    }
    
    // Test 2: Test exact match
    echo "\n=== Test Exact Match for 'Beratungsgespräch' ===\n";
    $exactMatch = Service::where('company_id', $branch->company_id)
        ->where(function ($query) use ($branch) {
            $query->where('branch_id', $branch->id)
                  ->orWhereNull('branch_id');
        })
        ->where('is_active', true)
        ->whereRaw('LOWER(name) = ?', ['beratungsgespräch'])
        ->first();
    
    if ($exactMatch) {
        echo "✅ Found exact match: {$exactMatch->name}\n";
    } else {
        echo "❌ No exact match found\n";
    }
    
    // Test 3: Test EventTypeMatchingService
    echo "\n=== Test EventTypeMatchingService ===\n";
    $eventTypeMatchingService = new EventTypeMatchingService();
    
    $testCases = [
        'Beratungsgespräch' => 'Exact match',
        'Beratung' => 'Partial match',
        'gespräch' => 'Partial match',
        'Beratungstermin' => 'Similar word'
    ];
    
    foreach ($testCases as $request => $description) {
        echo "\nTesting: '$request' ($description)\n";
        $match = $eventTypeMatchingService->findMatchingEventType($request, $branch);
        
        if ($match) {
            echo "✅ Match found:\n";
            echo "  - Service: {$match['service']->name}\n";
            echo "  - Event Type: {$match['event_type']->name}\n";
        } else {
            echo "❌ No match found\n";
            
            // Debug: Check if service was found
            $normalizedRequest = trim(strtolower($request));
            $debugServices = Service::where('company_id', $branch->company_id)
                ->where(function ($query) use ($branch) {
                    $query->where('branch_id', $branch->id)
                          ->orWhereNull('branch_id');
                })
                ->where('is_active', true)
                ->whereRaw('LOWER(name) LIKE ?', ['%' . $normalizedRequest . '%'])
                ->get();
            
            echo "  Debug: Found {$debugServices->count()} services with partial match\n";
            
            // Check mappings
            $mappingCount = DB::table('service_event_type_mappings')
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->count();
            
            echo "  Debug: Total active mappings in company: {$mappingCount}\n";
        }
    }
    
    // Test 4: Check service-event type mappings
    echo "\n=== Check Service-EventType Mappings ===\n";
    $mappings = DB::table('service_event_type_mappings as sem')
        ->join('services as s', 'sem.service_id', '=', 's.id')
        ->join('calcom_event_types as cet', 'sem.calcom_event_type_id', '=', 'cet.calcom_numeric_event_type_id')
        ->where('sem.company_id', $company->id)
        ->where('sem.is_active', true)
        ->select(
            's.name as service_name',
            's.id as service_id',
            'cet.name as event_type_name',
            'cet.calcom_numeric_event_type_id',
            'sem.branch_id'
        )
        ->get();
    
    echo "Found {$mappings->count()} mappings:\n";
    foreach ($mappings as $mapping) {
        echo "- Service: {$mapping->service_name} (ID: {$mapping->service_id}) => Event Type: {$mapping->event_type_name} (ID: {$mapping->calcom_numeric_event_type_id})\n";
        echo "  Mapping Branch: " . ($mapping->branch_id ?? 'NULL') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n";