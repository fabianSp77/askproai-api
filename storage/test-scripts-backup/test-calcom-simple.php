<?php
/**
 * Simple test to check Cal.com integration
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MCP\CalcomMCPServer;

echo "=== Simple Cal.com Test ===\n\n";

try {
    $calcomMCP = new CalcomMCPServer();
    
    // Test 1: Get event types
    echo "1. Testing getEventTypes...\n";
    $result = $calcomMCP->getEventTypes(['company_id' => 1]);
    
    if (isset($result['error'])) {
        echo "   Error: " . $result['error'] . "\n";
        if (isset($result['message'])) {
            echo "   Details: " . $result['message'] . "\n";
        }
    } else {
        echo "   Success! Found " . ($result['count'] ?? 0) . " event types\n";
        if (isset($result['event_types'])) {
            foreach ($result['event_types'] as $et) {
                echo "   - {$et['title']} (ID: {$et['id']})\n";
            }
        }
    }
    
    // Test 2: Sync event types with details
    echo "\n2. Testing syncEventTypesWithDetails...\n";
    $syncResult = $calcomMCP->syncEventTypesWithDetails(['company_id' => 1]);
    
    if (isset($syncResult['error'])) {
        echo "   Error: " . $syncResult['error'] . "\n";
        if (isset($syncResult['message'])) {
            echo "   Details: " . $syncResult['message'] . "\n";
        }
    } else {
        echo "   " . ($syncResult['message'] ?? 'Success!') . "\n";
    }
    
    // Test 3: Sync users for a specific event type
    echo "\n3. Testing syncEventTypeUsers for event type 2563193...\n";
    $userSyncResult = $calcomMCP->syncEventTypeUsers([
        'event_type_id' => 2563193,
        'company_id' => 1
    ]);
    
    if ($userSyncResult['success']) {
        echo "   Success!\n";
        echo "   " . ($userSyncResult['message'] ?? '') . "\n";
        
        if (isset($userSyncResult['mapping_results'])) {
            echo "   Mapping results:\n";
            foreach ($userSyncResult['mapping_results'] as $mapping) {
                $user = $mapping['calcom_user'] ?? [];
                echo "   - Cal.com User: " . ($user['name'] ?? 'Unknown') . " (" . ($user['email'] ?? 'No email') . ")\n";
                echo "     Status: " . $mapping['status'] . "\n";
                if ($mapping['status'] === 'matched' && isset($mapping['local_staff'])) {
                    echo "     Matched to: " . $mapping['local_staff']['name'] . "\n";
                } elseif (isset($mapping['reason'])) {
                    echo "     Reason: " . $mapping['reason'] . "\n";
                }
            }
        }
    } else {
        echo "   Error: " . ($userSyncResult['error'] ?? 'Unknown') . "\n";
        if (isset($userSyncResult['message'])) {
            echo "   Details: " . $userSyncResult['message'] . "\n";
        }
    }
    
    // Show the process explanation
    echo "\n=== Erklärung des Staff-Event Type Mapping Prozesses ===\n";
    echo "1. **Event Types = Dienstleistungen**: Jeder Cal.com Event Type repräsentiert eine Dienstleistung\n";
    echo "2. **Cal.com Users = Mitarbeiter**: Die in Cal.com zugewiesenen Benutzer sind die Mitarbeiter\n";
    echo "3. **Synchronisation**: Das System holt die Event Type Details von Cal.com und:\n";
    echo "   - Findet die zugewiesenen Benutzer (hosts/users)\n";
    echo "   - Sucht entsprechende Mitarbeiter in der lokalen Datenbank\n";
    echo "   - Erstellt Verknüpfungen in der staff_event_types Tabelle\n";
    echo "4. **Matching-Strategie**:\n";
    echo "   - Zuerst nach Cal.com User ID (wenn bereits synchronisiert)\n";
    echo "   - Dann nach E-Mail-Adresse\n";
    echo "   - Zuletzt nach Namen (Fuzzy-Match)\n";
    echo "5. **Ergebnis**: Das System weiß nun, welche Mitarbeiter welche Dienstleistungen anbieten können\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";