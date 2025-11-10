<?php
/**
 * Fix parameter_mappings in LLM general_tools
 * Adds call_id template variable to all custom tools
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$llmId = 'llm_f3209286ed1caf6a75906d2645b9';

echo "=== FIX LLM GENERAL_TOOLS PARAMETER MAPPINGS ===\n\n";

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
    die("❌ Failed to fetch LLM. HTTP {$httpCode}\nResponse: {$response}\n");
}

$llm = json_decode($response, true);
echo "✅ Fetched LLM V{$llm['version']}\n";
echo "   Published: " . ($llm['is_published'] ? 'YES' : 'NO') . "\n\n";

// 2. Check general_tools
echo "2. Checking general_tools...\n";
if (!isset($llm['general_tools'])) {
    die("❌ No general_tools found in LLM\n");
}

$tools = $llm['general_tools'];
echo "✅ Found " . count($tools) . " tools in general_tools\n\n";

// 3. Fix parameter_mappings for custom tools
echo "3. Fixing parameter_mappings for custom tools...\n";

$fixedCount = 0;
$alreadyCorrect = 0;
$skipped = 0;

foreach ($tools as &$tool) {
    $toolName = $tool['name'] ?? 'unknown';
    $toolType = $tool['type'] ?? 'unknown';

    // Only fix custom tools
    if ($toolType !== 'custom') {
        echo "  ⏭️  {$toolName}: Skipping (type: {$toolType})\n";
        $skipped++;
        continue;
    }

    // Check if tool has call_id parameter
    $hasCallIdParam = isset($tool['parameters']['properties']['call_id']);

    if (!$hasCallIdParam) {
        echo "  ⏭️  {$toolName}: Skipping (no call_id parameter)\n";
        $skipped++;
        continue;
    }

    // Check current parameter_mapping
    $hasMapping = isset($tool['parameter_mapping']['call_id']);
    $mappingValue = $hasMapping ? $tool['parameter_mapping']['call_id'] : null;

    if ($mappingValue === '{{call_id}}') {
        echo "  ✅ {$toolName}: Already correct\n";
        $alreadyCorrect++;
    } else {
        echo "  🔧 {$toolName}: ";
        if ($hasMapping) {
            echo "Fixing (was: {$mappingValue})\n";
        } else {
            echo "Adding parameter_mapping\n";
        }

        // Set parameter_mapping
        if (!isset($tool['parameter_mapping'])) {
            $tool['parameter_mapping'] = [];
        }
        $tool['parameter_mapping']['call_id'] = '{{call_id}}';
        $fixedCount++;
    }
}
unset($tool);

echo "\n📊 Summary:\n";
echo "  - Fixed: {$fixedCount}\n";
echo "  - Already correct: {$alreadyCorrect}\n";
echo "  - Skipped: {$skipped}\n";
echo "  - Total custom tools: " . ($fixedCount + $alreadyCorrect) . "\n\n";

if ($fixedCount === 0) {
    echo "✅ No changes needed!\n";
    exit(0);
}

// 4. Update LLM
echo "4. Updating LLM...\n";
$llm['general_tools'] = $tools;

$ch = curl_init("https://api.retellai.com/update-retell-llm/{$llmId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($llm)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to update LLM. HTTP {$httpCode}\n";
    echo "Response: {$response}\n";
    exit(1);
}

$updated = json_decode($response, true);
$newVersion = $updated['version'] ?? 'unknown';
echo "✅ LLM updated to V{$newVersion}\n\n";

// 5. Publish LLM
echo "5. Publishing LLM V{$newVersion}...\n";
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
    echo "Response: {$response}\n";
    echo "\n⚠️ ACTION REQUIRED: Publish manually\n";
} else {
    echo "✅ LLM V{$newVersion} published!\n\n";
}

// 6. Verify
echo "6. Verifying...\n";
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

// Check tools
echo "   Checking parameter_mappings:\n";
foreach ($verifyLlm['general_tools'] as $tool) {
    if ($tool['type'] === 'custom' && isset($tool['parameters']['properties']['call_id'])) {
        $mapping = $tool['parameter_mapping']['call_id'] ?? 'MISSING';
        $status = $mapping === '{{call_id}}' ? '✅' : '❌';
        echo "   {$status} {$tool['name']}: {$mapping}\n";
    }
}

echo "\n🎉 FIX COMPLETE!\n";
echo "\nNext: Make test call to verify call_id is no longer \"1\"\n";

echo "\n=== END FIX ===\n";
