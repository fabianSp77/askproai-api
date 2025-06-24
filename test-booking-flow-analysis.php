#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\AppointmentBookingService;
use App\Services\EventTypeMatchingService;
use App\Models\Branch;
use App\Models\Call;
use App\Models\Company;
use App\Models\Service;
use App\Models\CalcomEventType;
use Illuminate\Support\Facades\Log;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Enable debugging
Log::getDefaultDriver();
config(['logging.default' => 'single']);

echo "\n=== Testing Booking Flow Analysis ===\n\n";

try {
    // Get a test company and branch
    $company = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)->first();
    if (!$company) {
        throw new Exception("No company found in database");
    }
    
    $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)->first();
    if (!$branch) {
        throw new Exception("No branch found for company");
    }
    
    echo "Using Company: {$company->name} (ID: {$company->id})\n";
    echo "Using Branch: {$branch->name} (ID: {$branch->id})\n\n";
    
    // Set company context for tenant scope
    app()->instance('current_company_id', $company->id);
    
    // Test EventTypeMatchingService
    $eventTypeMatchingService = new EventTypeMatchingService();
    
    // Test scenarios
    $testScenarios = [
        [
            'description' => 'Customer says "Ich möchte einen Beratungstermin"',
            'service_request' => 'Beratungstermin',
            'staff_name' => null
        ],
        [
            'description' => 'Customer says "Termin bei Fabian Spitzer"',
            'service_request' => 'Termin',
            'staff_name' => 'Fabian Spitzer'
        ],
        [
            'description' => 'Customer gives vague description "Ich brauche Hilfe"',
            'service_request' => 'Hilfe',
            'staff_name' => null
        ],
        [
            'description' => 'Customer says "Haarschnitt für morgen"',
            'service_request' => 'Haarschnitt',
            'staff_name' => null
        ]
    ];
    
    echo "=== Testing Event Type Matching ===\n\n";
    
    foreach ($testScenarios as $scenario) {
        echo "Scenario: {$scenario['description']}\n";
        echo "Service Request: '{$scenario['service_request']}'\n";
        echo "Staff Name: " . ($scenario['staff_name'] ?: 'None') . "\n";
        
        $match = $eventTypeMatchingService->findMatchingEventType(
            $scenario['service_request'],
            $branch,
            $scenario['staff_name']
        );
        
        if ($match) {
            echo "✅ Match Found!\n";
            echo "  - Service: {$match['service']->name} (ID: {$match['service']->id})\n";
            echo "  - Event Type: {$match['event_type']->name} (ID: {$match['event_type']->id})\n";
            echo "  - Duration: {$match['duration_minutes']} minutes\n";
        } else {
            echo "❌ No Match Found\n";
        }
        echo "\n";
    }
    
    // Check available services and event types
    echo "=== Available Services in Branch ===\n";
    $services = Service::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $branch->company_id)
        ->where(function ($query) use ($branch) {
            $query->where('branch_id', $branch->id)
                  ->orWhereNull('branch_id');
        })
        ->where('is_active', true)
        ->get();
    
    foreach ($services as $service) {
        echo "- {$service->name} (ID: {$service->id})\n";
    }
    
    echo "\n=== Available Event Types ===\n";
    $eventTypes = CalcomEventType::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->where('is_active', true)
        ->get();
    
    foreach ($eventTypes as $eventType) {
        echo "- {$eventType->name} (ID: {$eventType->calcom_numeric_event_type_id})\n";
    }
    
    // Check service to event type mappings
    echo "\n=== Service to Event Type Mappings ===\n";
    $mappings = DB::table('service_event_type_mappings')
        ->join('services', 'service_event_type_mappings.service_id', '=', 'services.id')
        ->join('calcom_event_types', 'service_event_type_mappings.calcom_event_type_id', '=', 'calcom_event_types.calcom_numeric_event_type_id')
        ->where('service_event_type_mappings.company_id', $company->id)
        ->where('service_event_type_mappings.is_active', true)
        ->select(
            'services.name as service_name',
            'calcom_event_types.name as event_type_name',
            'service_event_type_mappings.keywords',
            'service_event_type_mappings.priority'
        )
        ->get();
    
    if ($mappings->isEmpty()) {
        echo "❌ No active mappings found!\n";
    } else {
        foreach ($mappings as $mapping) {
            echo "- Service: {$mapping->service_name} => Event Type: {$mapping->event_type_name}\n";
            if ($mapping->keywords) {
                echo "  Keywords: {$mapping->keywords}\n";
            }
            echo "  Priority: {$mapping->priority}\n";
        }
    }
    
    // Test complete booking flow
    echo "\n=== Testing Complete Booking Flow ===\n";
    
    // Simulate call data from Retell webhook
    $callData = [
        'datum' => date('d.m.Y', strtotime('+1 day')),
        'uhrzeit' => '14:30',
        'name' => 'Test Kunde',
        'telefonnummer' => '+49 170 1234567',
        'email' => 'test@example.com',
        'dienstleistung' => 'Beratung',
        'mitarbeiter_wunsch' => null
    ];
    
    // Create a test call
    $call = new Call();
    $call->id = 999999;
    $call->company_id = $company->id;
    $call->branch_id = $branch->id;
    $call->from_number = $callData['telefonnummer'];
    $call->correlation_id = 'test-' . uniqid();
    
    $bookingService = new AppointmentBookingService();
    
    echo "Attempting to book appointment with:\n";
    echo "- Date/Time: {$callData['datum']} {$callData['uhrzeit']}\n";
    echo "- Customer: {$callData['name']}\n";
    echo "- Service Request: {$callData['dienstleistung']}\n";
    
    // Note: This would actually create an appointment in a real test
    // For analysis, we'll just check if the flow would work
    
    echo "\n=== Analysis Complete ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n";