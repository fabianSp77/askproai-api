<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use App\Models\Company;
use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\Http;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $company = Company::find(1);
    
    if (!$company) {
        echo "Error: Company not found\n";
        exit(1);
    }
    
    echo "=== Cal.com Team ID Fix ===\n";
    echo "Company: {$company->name}\n";
    echo "Current Team ID: " . ($company->calcom_team_id ?: 'NOT SET') . "\n\n";
    
    if (!$company->calcom_api_key) {
        echo "Error: No Cal.com API key configured\n";
        exit(1);
    }
    
    // 1. Get user info to find team memberships
    echo "1. Fetching user teams...\n";
    
    // Cal.com v1 API uses query parameter for API key
    $response = Http::get('https://api.cal.com/v1/teams', [
        'apiKey' => $company->calcom_api_key
    ]);
    
    if ($response->successful()) {
        $teams = $response->json()['teams'] ?? [];
        
        if (empty($teams)) {
            echo "No teams found. Let's check memberships...\n";
            
            // Try memberships endpoint
            $membershipResponse = Http::get('https://api.cal.com/v1/memberships', [
                'apiKey' => $company->calcom_api_key
            ]);
            
            if ($membershipResponse->successful()) {
                $memberships = $membershipResponse->json()['memberships'] ?? [];
                echo "Found " . count($memberships) . " memberships\n";
                
                foreach ($memberships as $membership) {
                    echo "\nTeam: " . ($membership['team']['name'] ?? 'N/A') . "\n";
                    echo "Team ID: " . ($membership['team']['id'] ?? 'N/A') . "\n";
                    echo "Role: " . ($membership['role'] ?? 'N/A') . "\n";
                    
                    if (!$company->calcom_team_id && isset($membership['team']['id'])) {
                        $teamId = $membership['team']['id'];
                        echo "\n✅ Setting team ID to: {$teamId}\n";
                        
                        $company->calcom_team_id = $teamId;
                        $company->save();
                        
                        echo "Team ID saved successfully!\n";
                        break;
                    }
                }
            }
        } else {
            echo "Found " . count($teams) . " teams:\n";
            
            foreach ($teams as $team) {
                echo "\nTeam: " . ($team['name'] ?? 'N/A') . "\n";
                echo "Team ID: " . ($team['id'] ?? 'N/A') . "\n";
                echo "Slug: " . ($team['slug'] ?? 'N/A') . "\n";
                
                // Use first team if no team ID is set
                if (!$company->calcom_team_id && isset($team['id'])) {
                    $teamId = $team['id'];
                    echo "\n✅ Setting team ID to: {$teamId}\n";
                    
                    $company->calcom_team_id = $teamId;
                    $company->save();
                    
                    echo "Team ID saved successfully!\n";
                    break;
                }
            }
        }
    } else {
        echo "❌ Failed to fetch teams!\n";
        echo "Status: " . $response->status() . "\n";
        echo "Error: " . $response->body() . "\n";
    }
    
    // 2. Test event types with team ID
    if ($company->calcom_team_id) {
        echo "\n2. Testing event types with team ID...\n";
        
        $eventTypesResponse = Http::get('https://api.cal.com/v1/event-types', [
            'apiKey' => $company->calcom_api_key,
            'teamId' => $company->calcom_team_id
        ]);
        
        if ($eventTypesResponse->successful()) {
            $eventTypes = $eventTypesResponse->json()['event_types'] ?? [];
            echo "✅ Found " . count($eventTypes) . " event types\n";
            
            foreach ($eventTypes as $et) {
                echo "- " . ($et['title'] ?? 'N/A') . " (ID: " . ($et['id'] ?? 'N/A') . ")\n";
            }
        } else {
            echo "❌ Failed to fetch event types\n";
        }
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}