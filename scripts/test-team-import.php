<?php

/**
 * Test script for team-based cal.com integration
 *
 * This script tests the complete flow of:
 * 1. Setting up a company with a team
 * 2. Importing team event types
 * 3. Validating team ownership
 * 4. Assigning services to branches
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Services\CalcomV2Service;
use App\Jobs\ImportTeamEventTypesJob;
use Illuminate\Support\Facades\Log;

echo "\n🧪 Team-based Cal.com Integration Test\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Test company ID 15 (askproai Hauptsitz München)
$companyId = 15;

try {
    // Step 1: Get the company
    echo "📋 Step 1: Loading company...\n";
    $company = Company::find($companyId);

    if (!$company) {
        throw new Exception("Company ID {$companyId} not found!");
    }

    echo "✓ Company: {$company->name}\n";
    echo "  - Team ID: " . ($company->calcom_team_id ?? 'Not set') . "\n";
    echo "  - Team Name: " . ($company->calcom_team_name ?? 'Not set') . "\n";
    echo "  - Last Sync: " . ($company->last_team_sync ? $company->last_team_sync->format('Y-m-d H:i:s') : 'Never') . "\n";
    echo "  - Sync Status: " . ($company->team_sync_status ?? 'pending') . "\n\n";

    // Step 2: Check if team is configured
    if (!$company->calcom_team_id) {
        echo "⚠️ Warning: Company has no Cal.com team ID configured.\n";
        echo "Please set the calcom_team_id in the admin panel first.\n";
        echo "Example: UPDATE companies SET calcom_team_id = YOUR_TEAM_ID WHERE id = {$companyId};\n";
        exit(1);
    }

    // Step 3: Test CalcomV2Service
    echo "📋 Step 2: Testing CalcomV2Service...\n";
    $calcomService = new CalcomV2Service($company);

    // Test fetching team details
    echo "  - Fetching team details...\n";
    $teamDetails = $calcomService->getTeamDetails($company->calcom_team_id);

    if ($teamDetails) {
        echo "  ✓ Team found: " . ($teamDetails['name'] ?? 'Unknown') . "\n";

        // Update company with team name if not set
        if (!$company->calcom_team_name && isset($teamDetails['name'])) {
            $company->update(['calcom_team_name' => $teamDetails['name']]);
            echo "  ✓ Updated company with team name\n";
        }
    } else {
        echo "  ⚠️ Could not fetch team details\n";
    }

    // Step 4: Import team event types
    echo "\n📋 Step 3: Importing team event types...\n";

    $result = $calcomService->importTeamEventTypes($company);

    if ($result['success']) {
        echo "✓ Import successful!\n";
        echo "  - Total event types: " . ($result['summary']['total'] ?? 0) . "\n";
        echo "  - Imported: " . ($result['summary']['imported'] ?? 0) . "\n";
        echo "  - Updated: " . ($result['summary']['updated'] ?? 0) . "\n";
        echo "  - Failed: " . ($result['summary']['failed'] ?? 0) . "\n";

        // Show some imported services
        if (isset($result['results']) && count($result['results']) > 0) {
            echo "\n  Sample imported services:\n";
            foreach (array_slice($result['results'], 0, 5) as $item) {
                echo "    - Event Type ID {$item['event_type_id']}: {$item['action']}\n";
                if (isset($item['service_id'])) {
                    $service = \App\Models\Service::find($item['service_id']);
                    if ($service) {
                        echo "      Name: " . $service->name . "\n";
                    }
                }
            }
        }
    } else {
        echo "✗ Import failed: " . ($result['message'] ?? 'Unknown error') . "\n";
        if (isset($result['error'])) {
            if (is_array($result['error'])) {
                echo "  Error: " . json_encode($result['error']) . "\n";
            } else {
                echo "  Error: " . $result['error'] . "\n";
            }
        }
    }

    // Step 5: Sync team members
    echo "\n📋 Step 4: Syncing team members...\n";

    $membersResult = $calcomService->syncTeamMembers($company);

    if ($membersResult['success']) {
        echo "✓ Team members synced!\n";
        echo "  - Members count: " . ($membersResult['members_count'] ?? 0) . "\n";

        // Show team members
        $members = \DB::table('calcom_team_members')
            ->where('company_id', $company->id)
            ->where('calcom_team_id', $company->calcom_team_id)
            ->limit(5)
            ->get();

        if ($members->count() > 0) {
            echo "\n  Team members:\n";
            foreach ($members as $member) {
                echo "    - {$member->name} ({$member->email}) - Role: {$member->role}\n";
            }
        }
    } else {
        echo "⚠️ Team member sync failed: " . ($membersResult['message'] ?? 'Unknown error') . "\n";
    }

    // Step 6: Test team validation
    echo "\n📋 Step 5: Testing team validation...\n";

    // Get a service that should belong to the team
    $service = \App\Models\Service::where('company_id', $company->id)
        ->whereNotNull('calcom_event_type_id')
        ->first();

    if ($service) {
        echo "  Testing service: {$service->name}\n";
        echo "  Cal.com Event Type ID: {$service->calcom_event_type_id}\n";

        $isOwned = $company->ownsService($service->calcom_event_type_id);

        if ($isOwned) {
            echo "  ✓ Service correctly validated as owned by company's team\n";
        } else {
            echo "  ✗ Service NOT validated as owned by team (may need resync)\n";
        }
    } else {
        echo "  ⚠️ No services found with cal.com event type IDs\n";
    }

    // Step 7: Show branch assignments
    echo "\n📋 Step 6: Checking branch service assignments...\n";

    $branches = $company->branches()->with('services')->get();

    foreach ($branches as $branch) {
        echo "  Branch: {$branch->name}\n";
        $activeServices = $branch->services()->wherePivot('is_active', true)->count();
        $totalServices = $branch->services()->count();
        echo "    - Active Services: {$activeServices}\n";
        echo "    - Total Services: {$totalServices}\n";
    }

    // Final summary
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ Test completed successfully!\n\n";

    echo "Summary:\n";
    echo "  - Company: {$company->name}\n";
    echo "  - Team ID: {$company->calcom_team_id}\n";
    echo "  - Team Name: " . ($company->calcom_team_name ?? 'Not set') . "\n";
    echo "  - Event Types: {$company->team_event_type_count}\n";
    echo "  - Team Members: {$company->team_member_count}\n";
    echo "  - Last Sync: " . ($company->last_team_sync ? $company->last_team_sync->format('Y-m-d H:i:s') : 'Never') . "\n";
    echo "  - Sync Status: {$company->team_sync_status}\n";

    if ($company->team_sync_error) {
        echo "  - Sync Error: {$company->team_sync_error}\n";
    }

} catch (\Exception $e) {
    echo "\n❌ Test failed with error:\n";
    echo "  " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n";