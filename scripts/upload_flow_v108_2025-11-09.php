<?php
/**
 * Upload Flow V108 to Retell API
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = config('services.retellai.api_key');

echo "=== UPLOAD FLOW V108 ===\n\n";

// Load prepared flow
$flow = json_decode(file_get_contents(__DIR__ . '/../flow_v108_ready.json'), true);

echo "Prepared flow loaded\n";
echo "Total nodes: " . count($flow['nodes']) . "\n\n";

// Upload to API
echo "Uploading to Retell API...\n";

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

// Verify the upload
echo "Verifying upload...\n";

$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);
$response = curl_exec($ch);
curl_close($ch);

$verifiedFlow = json_decode($response, true);

// Verification checks
$checks = [
    'node_collect_booking_info removed' => true,
    'node_collect_phone exists' => false,
    'customer_phone variable exists' => false,
    'direct edge extract->check' => false
];

// Check 1: node_collect_booking_info removed?
foreach ($verifiedFlow['nodes'] as $node) {
    if ($node['id'] === 'node_collect_booking_info') {
        $checks['node_collect_booking_info removed'] = false;
    }
    if ($node['id'] === 'node_collect_phone') {
        $checks['node_collect_phone exists'] = true;
    }
    if ($node['id'] === 'node_extract_booking_variables') {
        foreach ($node['variables'] as $var) {
            if ($var['name'] === 'customer_phone') {
                $checks['customer_phone variable exists'] = true;
            }
        }
        foreach ($node['edges'] as $edge) {
            if ($edge['destination_node_id'] === 'func_check_availability') {
                $checks['direct edge extract->check'] = true;
            }
        }
    }
}

echo "\nVerification Results:\n";
foreach ($checks as $check => $passed) {
    $status = $passed ? '✅' : '❌';
    echo "  {$status} {$check}\n";
}

$allPassed = !in_array(false, $checks, true);

if ($allPassed) {
    echo "\n✅ ALL CHECKS PASSED\n\n";
} else {
    echo "\n❌ SOME CHECKS FAILED\n\n";
}

echo "=== SUMMARY ===\n\n";
echo "✅ Flow uploaded: V{$newVersion}\n";
echo "✅ Changes verified: " . ($allPassed ? 'YES' : 'NO') . "\n";
echo "📌 Published: NO (User must publish manually)\n\n";

echo "=== FIXES APPLIED ===\n\n";
echo "1️⃣  No more double questions\n";
echo "   - node_collect_booking_info removed\n";
echo "   - Direct edge: extract → check_availability\n\n";

echo "2️⃣  No more unnecessary confirmation\n";
echo "   - Agent goes directly to availability check\n";
echo "   - No waiting for user confirmation\n\n";

echo "3️⃣  Phone number collection\n";
echo "   - customer_phone variable added\n";
echo "   - node_collect_phone asks if missing\n";
echo "   - Fixes booking failure\n\n";

echo "=== NEXT STEPS ===\n\n";
echo "1. Publish V{$newVersion} in Retell Dashboard\n";
echo "2. Make VOICE CALL test\n";
echo "3. Verify problems are fixed:\n";
echo "   ✓ No double questions\n";
echo "   ✓ No unnecessary confirmation\n";
echo "   ✓ Booking succeeds with phone number\n\n";

echo "=== END ===\n";
