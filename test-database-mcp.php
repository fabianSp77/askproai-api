<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Test DatabaseMCPServer directly
try {
    $databaseMCP = $app->make(\App\Services\MCP\DatabaseMCPServer::class);
    
    echo "=== Testing DatabaseMCPServer ===\n\n";
    
    // Test 1: Check phone numbers table
    echo "1. Phone numbers in database:\n";
    $phoneNumbers = $databaseMCP->query("SELECT * FROM phone_numbers WHERE number LIKE '%30837%'");
    foreach ($phoneNumbers['results'] as $phone) {
        echo "   - ID: {$phone['id']}, Number: {$phone['number']}, Branch: {$phone['branch_id']}\n";
    }
    
    // Test 2: Check branches
    echo "\n2. Branches in database:\n";
    $branches = $databaseMCP->query("SELECT uuid, name, is_active, calcom_event_type_id FROM branches WHERE company_id = 85");
    foreach ($branches['results'] as $branch) {
        echo "   - UUID: {$branch['uuid']}, Name: {$branch['name']}, Active: {$branch['is_active']}, Event Type: {$branch['calcom_event_type_id']}\n";
    }
    
    // Test 3: Test the exact query from WebhookMCPServer
    echo "\n3. Testing phone resolution query:\n";
    $phoneToTest = '+493083793369';
    $result = $databaseMCP->query(
        "SELECT pn.*, b.company_id, b.name as branch_name, b.calcom_event_type_id 
         FROM phone_numbers pn
         JOIN branches b ON pn.branch_id = b.uuid
         WHERE pn.number = ? AND pn.active = 1 AND b.is_active = 1
         LIMIT 1",
        [$phoneToTest]
    );
    
    if (!empty($result['results'])) {
        echo "   ✅ Found phone mapping!\n";
        print_r($result['results'][0]);
    } else {
        echo "   ❌ No phone mapping found for: $phoneToTest\n";
        
        // Try without +
        $phoneWithoutPlus = substr($phoneToTest, 1);
        echo "\n   Trying without +: $phoneWithoutPlus\n";
        $result2 = $databaseMCP->query(
            "SELECT pn.*, b.company_id, b.name as branch_name, b.calcom_event_type_id 
             FROM phone_numbers pn
             JOIN branches b ON pn.branch_id = b.uuid
             WHERE pn.number = ? AND pn.active = 1 AND b.is_active = 1
             LIMIT 1",
            [$phoneWithoutPlus]
        );
        
        if (!empty($result2['results'])) {
            echo "   ✅ Found with alternate format!\n";
            print_r($result2['results'][0]);
        } else {
            echo "   ❌ Still not found\n";
        }
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}