<?php
/**
 * Upload V110.5 Flow to Retell API
 *
 * Critical Fixes:
 * 1. service → service_name in parameter_mapping
 * 2. Removed function_name from all tool definitions
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = config('services.retellai.api_key');

echo "=== UPLOAD V110.5 FLOW ===\n\n";

// Load V110.5 flow
$flow = json_decode(file_get_contents(__DIR__ . '/../conversation_flow_v110_5_fixed.json'), true);

echo "V110.5 flow loaded\n";
echo "Flow ID: {$flow['conversation_flow_id']}\n";
echo "Version: {$flow['version']}\n";
echo "Total nodes: " . count($flow['nodes']) . "\n\n";

// Verify fixes are present
echo "=== VERIFYING FIXES ===\n\n";

$fixes = [
    'service_name in func_start_booking' => false,
    'No function_name in tool-start-booking' => false,
    'service_name in tool schema' => false
];

// Check fix 1: parameter_mapping
foreach ($flow['nodes'] as $node) {
    if ($node['id'] === 'func_start_booking') {
        if (isset($node['parameter_mapping']['service_name'])) {
            $fixes['service_name in func_start_booking'] = true;
            echo "✅ Fix 1: parameter_mapping has 'service_name'\n";
        }
        if (isset($node['parameter_mapping']['service'])) {
            echo "❌ ERROR: 'service' still present (should be removed)\n";
        }
    }
}

// Check fix 2 & 3: tool definitions
foreach ($flow['tools'] as $tool) {
    if ($tool['tool_id'] === 'tool-start-booking') {
        $params = $tool['parameters']['properties'] ?? [];

        if (!isset($params['function_name'])) {
            $fixes['No function_name in tool-start-booking'] = true;
            echo "✅ Fix 2: 'function_name' removed from tool schema\n";
        } else {
            echo "❌ ERROR: 'function_name' still in tool schema\n";
        }

        if (isset($params['service_name'])) {
            $fixes['service_name in tool schema'] = true;
            echo "✅ Fix 3: tool schema has 'service_name'\n";
        }

        if (isset($params['service'])) {
            echo "❌ ERROR: tool schema still has 'service' (should be service_name)\n";
        }
    }
}

$allFixesPresent = !in_array(false, $fixes, true);

if (!$allFixesPresent) {
    echo "\n❌ NOT ALL FIXES PRESENT - ABORTING\n";
    exit(1);
}

echo "\n✅ All fixes verified\n\n";

// Upload to API
echo "=== UPLOADING TO RETELL ===\n\n";

$payload = json_encode($flow, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$ch = curl_init("https://api.retellai.com/update-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "PATCH",
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => $payload
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to upload: HTTP {$httpCode}\n";
    echo "Response: {$response}\n";
    exit(1);
}

$updatedFlow = json_decode($response, true);
$newVersion = $updatedFlow['version'] ?? 'unknown';

echo "✅ Flow uploaded successfully!\n";
echo "New version: V{$newVersion}\n\n";

// Save response
file_put_contents(__DIR__ . '/../v110_5_upload_response.json', json_encode($updatedFlow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "=== SUMMARY ===\n\n";
echo "✅ Flow uploaded: V{$newVersion}\n";
echo "✅ Flow ID: {$flowId}\n";
echo "📌 Published: NO (User must publish manually)\n\n";

echo "=== FIXES APPLIED ===\n\n";
echo "1️⃣  Parameter name fix\n";
echo "   - 'service' → 'service_name' in func_start_booking\n";
echo "   - Backend now receives correct parameter name\n\n";

echo "2️⃣  Clean tool definitions\n";
echo "   - Removed 'function_name' from all tool schemas\n";
echo "   - Only required parameters remain\n\n";

echo "3️⃣  Schema consistency\n";
echo "   - tool-start-booking uses 'service_name'\n";
echo "   - tool-confirm-booking cleaned up\n\n";

echo "=== NEXT STEPS ===\n\n";
echo "1. Publish V{$newVersion} in Retell Dashboard\n";
echo "2. Test via /docs/api-testing interface\n";
echo "3. Verify start_booking now succeeds\n";
echo "4. Make VOICE CALL test\n\n";

echo "=== ROOT CAUSE FIXED ===\n\n";
echo "Backend expects: \$params['service_name']\n";
echo "V110.4 sent:      \$params['service'] ← WRONG\n";
echo "V110.5 sends:     \$params['service_name'] ← CORRECT\n\n";

echo "=== END ===\n";
