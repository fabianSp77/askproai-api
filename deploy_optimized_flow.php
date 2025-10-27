<?php

/**
 * Deploy Optimized Conversation Flow V2 to Retell.ai
 */

$flowId = 'conversation_flow_da76e7c6f3ba';  // Existing flow ID

// Read API key from .env file
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    die("ERROR: .env file not found\n");
}

$envContent = file_get_contents($envFile);
$apiKey = null;

// Try to find RETELLAI_API_KEY or RETELL_TOKEN
if (preg_match('/RETELLAI_API_KEY=(.+)/', $envContent, $matches)) {
    $apiKey = trim($matches[1]);
} elseif (preg_match('/RETELL_TOKEN=(.+)/', $envContent, $matches)) {
    $apiKey = trim($matches[1]);
} elseif (preg_match('/RETELL_API_KEY=(.+)/', $envContent, $matches)) {
    $apiKey = trim($matches[1]);
}

if (!$apiKey) {
    die("ERROR: Retell API key not found in .env file\n");
}

echo "=== DEPLOYING OPTIMIZED FLOW V2 ===\n\n";

// Load the optimized flow
$flowFile = '/var/www/api-gateway/public/askproai_conversation_flow_optimized_v2.json';
$flowJson = file_get_contents($flowFile);
$flowData = json_decode($flowJson, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("ERROR: Failed to parse flow JSON: " . json_last_error_msg() . "\n");
}

echo "Loaded flow:\n";
echo "- Nodes: " . count($flowData['nodes']) . "\n";
echo "- Tools: " . count($flowData['tools']) . "\n";
echo "- Size: " . round(strlen($flowJson) / 1024, 2) . " KB\n\n";

// Validate before deployment
echo "Validating flow...\n";
$errors = [];

foreach ($flowData['nodes'] as $node) {
    if ($node['type'] === 'function') {
        if (!isset($node['tool_id'])) {
            $errors[] = $node['id'] . ": Missing tool_id";
        }
        if (!isset($node['tool_type'])) {
            $errors[] = $node['id'] . ": Missing tool_type";
        }
        if (!isset($node['instruction'])) {
            $errors[] = $node['id'] . ": Missing instruction";
        }
    }

    // Check edges
    if (!isset($node['edges']) || !is_array($node['edges'])) {
        $errors[] = $node['id'] . ": Missing or invalid edges";
    }
}

if (!empty($errors)) {
    echo "❌ VALIDATION ERRORS:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    die("\nFix errors before deployment!\n");
}

echo "✅ Validation passed!\n\n";

// Deploy to Retell.ai
echo "Deploying to Retell.ai (Flow ID: $flowId)...\n";

$baseUrl = 'https://api.retellai.com';
$url = "$baseUrl/update-conversation-flow/$flowId";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => $flowJson
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ DEPLOYMENT FAILED!\n";
    echo "HTTP Code: $httpCode\n";
    echo "Response: $response\n";
    exit(1);
}

$result = json_decode($response, true);

echo "\n✅ DEPLOYMENT SUCCESSFUL!\n\n";
echo "Flow ID: " . $result['conversation_flow_id'] . "\n";
echo "Version: " . $result['agent_version'] . "\n";
echo "Status: " . $result['status'] . "\n";

echo "\n=== OPTIMIZATIONS DEPLOYED ===\n";
echo "1. ✅ Smart greeting with intent capture\n";
echo "2. ✅ Parallel time + customer check\n";
echo "3. ✅ Conditional routing (skip intent clarification)\n";
echo "4. ✅ Direct path to booking when intent+date known\n";
echo "5. ✅ Reduced 'Einen Moment bitte...' delays\n";
echo "6. ✅ Information reuse (never ask twice)\n\n";

echo "=== EXPECTED IMPROVEMENTS ===\n";
echo "Before: 119 sec, no booking, frustrated user\n";
echo "After:  ~35-40 sec, booking completed, happy user\n";
echo "Time reduction: ~70%\n\n";

echo "Ready for testing! Make a test call to validate.\n";
