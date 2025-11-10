<?php
/**
 * Fix customer_phone variable not saved - V109
 *
 * Problem: node_collect_phone is conversation type (doesn't extract variables)
 * User says phone but it's not saved to {{customer_phone}}
 *
 * Solution: Change node_collect_phone to extract_dynamic_variables type
 * This will capture phone from conversation and save to variable
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = config('services.retellai.api_key');

echo "=== FIX CUSTOMER_PHONE VARIABLE - V109 ===\n\n";

// STEP 1: Get current flow
echo "1. Fetching current flow V108...\n";
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

$flow = json_decode($response, true);
$currentVersion = $flow['version'];
echo "   Current version: V{$currentVersion}\n\n";

// STEP 2: Find and update node_collect_phone
echo "2. Updating node_collect_phone...\n";

$phoneNodeFound = false;
foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'node_collect_phone') {
        $phoneNodeFound = true;

        echo "   Found node_collect_phone (type: {$node['type']})\n";

        // Change to extract_dynamic_variables type
        $oldType = $node['type'];
        $node['type'] = 'extract_dynamic_variables';

        // Add customer_phone variable extraction
        $node['variables'] = [
            [
                'type' => 'string',
                'name' => 'customer_phone',
                'description' => 'Telefonnummer des Kunden für die Buchung'
            ]
        ];

        // Update instruction to be more concise for extraction node
        $node['instruction'] = [
            'type' => 'prompt',
            'text' => 'Frage nach Telefonnummer falls nicht vorhanden: "Für die Buchung brauche ich noch Ihre Telefonnummer."'
        ];

        echo "   ✅ Changed type: {$oldType} → extract_dynamic_variables\n";
        echo "   ✅ Added customer_phone variable extraction\n";
        echo "   ✅ Updated instruction\n\n";
        break;
    }
}
unset($node);

if (!$phoneNodeFound) {
    echo "   ❌ node_collect_phone not found!\n";
    exit(1);
}

// STEP 3: Verify the fix
echo "3. Verifying changes...\n";
foreach ($flow['nodes'] as $node) {
    if ($node['id'] === 'node_collect_phone') {
        $hasCorrectType = $node['type'] === 'extract_dynamic_variables';
        $hasPhoneVariable = false;

        if (isset($node['variables'])) {
            foreach ($node['variables'] as $var) {
                if ($var['name'] === 'customer_phone') {
                    $hasPhoneVariable = true;
                    break;
                }
            }
        }

        echo "   Type is extract_dynamic_variables: " . ($hasCorrectType ? '✅' : '❌') . "\n";
        echo "   Has customer_phone variable: " . ($hasPhoneVariable ? '✅' : '❌') . "\n\n";

        if (!$hasCorrectType || !$hasPhoneVariable) {
            echo "   ❌ Verification failed!\n";
            exit(1);
        }
    }
}

// STEP 4: Upload to API
echo "4. Uploading fixed flow...\n";

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
    echo "   ❌ Failed to upload: HTTP {$httpCode}\n";
    echo "   Response: {$response}\n";
    exit(1);
}

$updatedFlow = json_decode($response, true);
$newVersion = $updatedFlow['version'];

echo "   ✅ Flow uploaded successfully!\n";
echo "   New version: V{$newVersion}\n\n";

// STEP 5: Final verification
echo "5. Final verification...\n";

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

$verified = false;
foreach ($verifiedFlow['nodes'] as $node) {
    if ($node['id'] === 'node_collect_phone') {
        $isExtractType = $node['type'] === 'extract_dynamic_variables';
        $hasPhoneVar = false;

        if (isset($node['variables'])) {
            foreach ($node['variables'] as $var) {
                if ($var['name'] === 'customer_phone') {
                    $hasPhoneVar = true;
                    break;
                }
            }
        }

        $verified = $isExtractType && $hasPhoneVar;

        if ($verified) {
            echo "   ✅ node_collect_phone is extract_dynamic_variables\n";
            echo "   ✅ customer_phone variable exists\n\n";
        } else {
            echo "   ❌ Verification failed!\n";
            echo "   Type: {$node['type']}\n";
            echo "   Has phone variable: " . ($hasPhoneVar ? 'yes' : 'no') . "\n";
        }
    }
}

if (!$verified) {
    exit(1);
}

echo "=== SUMMARY ===\n\n";
echo "✅ Flow updated: V{$currentVersion} → V{$newVersion}\n";
echo "✅ node_collect_phone changed to extract_dynamic_variables\n";
echo "✅ customer_phone variable will now be saved\n";
echo "📌 Published: NO (User must publish manually)\n\n";

echo "=== HOW THIS FIXES THE ISSUE ===\n\n";
echo "Before:\n";
echo "  Agent: \"Telefonnummer bitte?\"\n";
echo "  User: \"0151 12345678\"\n";
echo "  {{customer_phone}}: NULL ❌ (conversation node doesn't extract)\n";
echo "  start_booking: Gets empty phone parameter\n\n";

echo "After:\n";
echo "  Agent: \"Telefonnummer bitte?\"\n";
echo "  User: \"0151 12345678\"\n";
echo "  {{customer_phone}}: \"0151 12345678\" ✅ (extracted automatically)\n";
echo "  start_booking: Gets real phone parameter\n\n";

echo "=== COMBINED WITH V108 FIX ===\n\n";
echo "V108: call_id extraction fixed\n";
echo "V109: customer_phone extraction fixed\n";
echo "Result: Booking flow will work end-to-end ✅\n\n";

echo "=== NEXT STEPS ===\n\n";
echo "1. Publish V{$newVersion} in Retell Dashboard\n";
echo "2. Make VOICE CALL test\n";
echo "3. Verify:\n";
echo "   ✓ Phone number is collected and saved\n";
echo "   ✓ Booking succeeds with correct call_id\n";
echo "   ✓ Appointment appears in database\n\n";

echo "=== END ===\n";
