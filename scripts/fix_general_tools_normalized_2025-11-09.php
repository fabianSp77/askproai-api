<?php
/**
 * Fix parameter_mappings with proper field normalization
 * Converts empty arrays to objects where needed
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$llmId = 'llm_f3209286ed1caf6a75906d2645b9';

echo "=== NORMALIZED FIX: LLM GENERAL_TOOLS ===\n\n";

// 1. Get LLM
echo "1. Fetching LLM...\n";
$ch = curl_init("https://api.retellai.com/get-retell-llm/{$llmId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ Failed to fetch LLM. HTTP {$httpCode}\n");
}

$llm = json_decode($response, true);
echo "✅ Fetched LLM V{$llm['version']}\n\n";

// 2. Fix general_tools
echo "2. Fixing general_tools...\n";
$tools = $llm['general_tools'];
$fixedCount = 0;
$normalizedCount = 0;

foreach ($tools as &$tool) {
    $toolName = $tool['name'] ?? 'unknown';
    $toolType = $tool['type'] ?? 'unknown';

    // Normalize custom tool fields (arrays -> objects where needed)
    if ($toolType === 'custom') {
        // Fix headers: [] -> {}
        if (isset($tool['headers']) && is_array($tool['headers']) && empty($tool['headers'])) {
            $tool['headers'] = new stdClass();
            $normalizedCount++;
        }

        // Fix query_params: [] -> {}
        if (isset($tool['query_params']) && is_array($tool['query_params']) && empty($tool['query_params'])) {
            $tool['query_params'] = new stdClass();
            $normalizedCount++;
        }

        // Fix response_variables: [] -> {}
        if (isset($tool['response_variables']) && is_array($tool['response_variables']) && empty($tool['response_variables'])) {
            $tool['response_variables'] = new stdClass();
            $normalizedCount++;
        }

        // Add parameter_mapping for tools with call_id parameter
        if (isset($tool['parameters']['properties']['call_id'])) {
            $hasMapping = isset($tool['parameter_mapping']['call_id']);
            $mappingValue = $hasMapping ? $tool['parameter_mapping']['call_id'] : null;

            if ($mappingValue !== '{{call_id}}') {
                echo "  🔧 {$toolName}: Adding parameter_mapping\n";
                if (!isset($tool['parameter_mapping'])) {
                    $tool['parameter_mapping'] = [];
                }
                $tool['parameter_mapping']['call_id'] = '{{call_id}}';
                $fixedCount++;
            } else {
                echo "  ✅ {$toolName}: Already correct\n";
            }
        }
    }
}
unset($tool);

echo "\n📊 Summary:\n";
echo "  - Added parameter_mapping: {$fixedCount}\n";
echo "  - Normalized fields: {$normalizedCount}\n\n";

if ($fixedCount === 0 && $normalizedCount === 0) {
    echo "✅ No changes needed!\n";
    exit(0);
}

// 3. Update LLM
echo "3. Updating LLM...\n";
$updatePayload = [
    'general_tools' => $tools
];

$jsonPayload = json_encode($updatePayload, JSON_UNESCAPED_SLASHES);

$ch = curl_init("https://api.retellai.com/update-retell-llm/{$llmId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => $jsonPayload
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to update LLM. HTTP {$httpCode}\n";
    echo "Response: {$response}\n\n";
    file_put_contents('/var/www/api-gateway/failed_normalized_payload_2025-11-09.json', $jsonPayload);
    echo "Payload saved for debugging\n";
    exit(1);
}

$updated = json_decode($response, true);
$newVersion = $updated['version'] ?? 'unknown';
echo "✅ LLM updated to V{$newVersion}\n\n";

// 4. Publish LLM
echo "4. Publishing LLM V{$newVersion}...\n";
$ch = curl_init("https://api.retellai.com/publish-retell-llm/{$llmId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode([])
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "⚠️ Auto-publish failed. HTTP {$httpCode}\n";
    echo "Response: {$response}\n\n";
} else {
    echo "✅ LLM V{$newVersion} published!\n\n";
}

// 5. Verify
echo "5. Verifying...\n";
$ch = curl_init("https://api.retellai.com/get-retell-llm/{$llmId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
$verifyLlm = json_decode($response, true);

echo "✅ Verification:\n";
echo "   Version: V{$verifyLlm['version']}\n";
echo "   Published: " . ($verifyLlm['is_published'] ? '✅ YES' : '❌ NO') . "\n\n";

// Check parameter_mappings
echo "   Parameter mappings:\n";
foreach ($verifyLlm['general_tools'] as $tool) {
    if ($tool['type'] === 'custom' && isset($tool['parameters']['properties']['call_id'])) {
        $mapping = $tool['parameter_mapping']['call_id'] ?? 'MISSING';
        $status = $mapping === '{{call_id}}' ? '✅' : '❌';
        echo "   {$status} {$tool['name']}: {$mapping}\n";
    }
}

echo "\n🎉 FIX COMPLETE!\n";
echo "\nNext step: Make a test call to verify call_id is now correct\n";
echo "\n=== END FIX ===\n";
