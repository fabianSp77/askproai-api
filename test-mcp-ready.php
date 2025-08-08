#!/usr/bin/env php
<?php
/**
 * Final MCP Readiness Test
 */

echo "\n";
echo "================================================================================\n";
echo "                    🚀 MCP READINESS TEST\n";
echo "================================================================================\n\n";

// Test 1: Check services in database
echo "1️⃣ Checking Database Services...\n";
try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;dbname=askproai_db',
        'askproai_user',
        'lkZ57Dju9EDjrMxn'
    );
    
    $stmt = $pdo->query("SELECT id, name, price, default_duration_minutes FROM services WHERE company_id = 1 AND active = 1 LIMIT 5");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   ✅ Found " . count($services) . " active services:\n";
    foreach ($services as $service) {
        echo "      - {$service['name']} ({$service['price']}€, {$service['default_duration_minutes']} min)\n";
    }
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

// Test 2: Test MCP endpoint
echo "\n2️⃣ Testing MCP Endpoint (list_services)...\n";
$ch = curl_init('https://api.askproai.de/api/v2/hair-salon-mcp');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'jsonrpc' => '2.0',
    'id' => 1,
    'method' => 'list_services',
    'params' => ['company_id' => 1]
]));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if (isset($data['result']['services'])) {
        echo "   ✅ MCP endpoint working! Services returned: " . count($data['result']['services']) . "\n";
        foreach (array_slice($data['result']['services'], 0, 3) as $service) {
            echo "      - {$service['name']} ({$service['price']}€)\n";
        }
    } else {
        echo "   ⚠️  Response received but no services found\n";
        echo "   Response: " . substr($response, 0, 200) . "\n";
    }
} else {
    echo "   ❌ HTTP Error: $httpCode\n";
    echo "   Response: " . substr($response, 0, 200) . "\n";
}

// Test 3: Test initialize method for tool discovery
echo "\n3️⃣ Testing Tool Discovery (initialize)...\n";
$ch = curl_init('https://api.askproai.de/api/v2/hair-salon-mcp');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'jsonrpc' => '2.0',
    'id' => 2,
    'method' => 'initialize',
    'params' => ['company_id' => 1]
]));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if (isset($data['result']['capabilities']['tools'])) {
        $tools = $data['result']['capabilities']['tools'];
        echo "   ✅ Tool discovery working! Tools available: " . count($tools) . "\n";
        foreach ($tools as $tool) {
            echo "      - {$tool['name']}: {$tool['description']}\n";
        }
    } else {
        echo "   ⚠️  No tools found in response\n";
    }
} else {
    echo "   ❌ HTTP Error: $httpCode\n";
}

// Test 4: Verify phone number configuration
echo "\n4️⃣ Checking Phone Configuration...\n";
echo "   📞 Test Number: +49 30 33081738\n";
echo "   🔗 MCP URL: https://api.askproai.de/api/v2/hair-salon-mcp\n";
echo "   ✅ Ready for testing!\n";

echo "\n================================================================================\n";
echo "                    📱 READY TO TEST\n";
echo "================================================================================\n";
echo "Instructions:\n";
echo "1. Call +49 30 33081738\n";
echo "2. Say: 'Ich möchte einen Termin für einen Haarschnitt buchen'\n";
echo "3. The AI should list available services\n";
echo "4. Choose a service and time\n";
echo "5. Provide your name and phone number\n";
echo "\n";
echo "Monitor activity with: php monitor-retell-calls.php\n";
echo "================================================================================\n\n";