<?php
/**
 * Test script for Cal.com Event Type User Synchronization
 * 
 * This demonstrates how the staff-event type mapping works:
 * 1. Fetches event type details from Cal.com (including assigned users)
 * 2. Maps Cal.com users to local staff members
 * 3. Creates staff_event_types assignments
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MCP\CalcomMCPServer;
use App\Models\Company;
use App\Models\CalcomEventType;

echo "=== Cal.com Event Type User Sync Test ===\n\n";

try {
    // Get the company
    $companyId = 1; // AskProAI Test Company
    $company = Company::find($companyId);
    
    if (!$company) {
        die("Company not found!\n");
    }
    
    echo "Company: {$company->name}\n";
    echo "Cal.com Team Slug: " . ($company->calcom_team_slug ?? 'Not set') . "\n\n";
    
    // Initialize MCP Server
    $calcomMCP = new CalcomMCPServer();
    
    // Test the specific event type ID (2563193)
    $eventTypeId = 2563193;
    echo "Testing Event Type ID: {$eventTypeId}\n\n";
    
    // Set company context for tenant scoping
    app()->instance('current_company_id', $companyId);
    
    // 1. First, check if we have this event type locally
    $localEventType = CalcomEventType::withoutGlobalScopes()
        ->where('calcom_numeric_event_type_id', $eventTypeId)
        ->where('company_id', $companyId)
        ->first();
        
    if ($localEventType) {
        echo "✓ Event Type found locally: {$localEventType->name}\n";
    } else {
        echo "✗ Event Type not found locally. You may need to sync event types first.\n";
    }
    
    // 2. Sync users for this event type
    echo "\n--- Syncing Event Type Users ---\n";
    
    $syncResult = $calcomMCP->syncEventTypeUsers([
        'event_type_id' => $eventTypeId,
        'company_id' => $companyId
    ]);
    
    if ($syncResult['success']) {
        echo "✓ Sync successful!\n";
        echo "Total hosts from Cal.com: {$syncResult['total_hosts']}\n";
        echo "Successfully matched: {$syncResult['matched']}\n";
        echo "Not matched: {$syncResult['not_matched']}\n\n";
        
        // Show mapping results
        if (!empty($syncResult['mapping_results'])) {
            echo "--- Mapping Details ---\n";
            foreach ($syncResult['mapping_results'] as $mapping) {
                $calcomUser = $mapping['calcom_user'];
                echo "\nCal.com User:\n";
                echo "  ID: " . ($calcomUser['id'] ?? 'N/A') . "\n";
                echo "  Name: " . ($calcomUser['name'] ?? 'N/A') . "\n";
                echo "  Email: " . ($calcomUser['email'] ?? 'N/A') . "\n";
                
                if ($mapping['status'] === 'matched') {
                    $localStaff = $mapping['local_staff'];
                    echo "  ✓ Matched to Local Staff:\n";
                    echo "    ID: {$localStaff['id']}\n";
                    echo "    Name: {$localStaff['name']}\n";
                    echo "    Email: {$localStaff['email']}\n";
                } else {
                    echo "  ✗ Not Matched: {$mapping['reason']}\n";
                    if (isset($mapping['suggestion'])) {
                        echo "  Suggestion: {$mapping['suggestion']}\n";
                    }
                }
            }
        }
    } else {
        echo "✗ Sync failed: " . ($syncResult['error'] ?? 'Unknown error') . "\n";
        if (isset($syncResult['message'])) {
            echo "Details: {$syncResult['message']}\n";
        }
    }
    
    // 3. Show current staff assignments for this event type
    echo "\n--- Current Staff Assignments ---\n";
    
    $assignments = DB::table('staff_event_types')
        ->join('staff', 'staff_event_types.staff_id', '=', 'staff.id')
        ->where('staff_event_types.calcom_event_type_id', $eventTypeId)
        ->select('staff.name', 'staff.email', 'staff_event_types.calcom_user_id', 'staff_event_types.created_at')
        ->get();
        
    if ($assignments->count() > 0) {
        foreach ($assignments as $assignment) {
            echo "Staff: {$assignment->name} ({$assignment->email})\n";
            echo "  Cal.com User ID: " . ($assignment->calcom_user_id ?? 'Not set') . "\n";
            echo "  Assigned at: {$assignment->created_at}\n\n";
        }
    } else {
        echo "No staff assignments found for this event type.\n";
    }
    
    // 4. Explain the process
    echo "\n--- How Staff-Event Type Mapping Works ---\n";
    echo "1. Cal.com Event Types = Services (e.g., 'Consultation', 'Haircut')\n";
    echo "2. Cal.com Users/Hosts = Staff members who can provide these services\n";
    echo "3. Mapping Process:\n";
    echo "   a) Fetch event type details from Cal.com API (includes assigned users)\n";
    echo "   b) Try to match Cal.com users to local staff by:\n";
    echo "      - Cal.com User ID (if previously synced)\n";
    echo "      - Email address\n";
    echo "      - Name (fuzzy match)\n";
    echo "   c) Create staff_event_types assignments for matched users\n";
    echo "4. This allows the system to know which staff can handle which services\n";
    echo "5. When booking appointments, only assigned staff are considered available\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";