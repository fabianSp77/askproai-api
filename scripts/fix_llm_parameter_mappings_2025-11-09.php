<?php
/**
 * Fix parameter_mappings in Retell LLM
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$llmId = 'llm_f3209286ed1caf6a75906d2645b9';

echo "=== FIX LLM PARAMETER MAPPINGS ===\n\n";

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
echo "✅ Fetched LLM\n";
echo "   Version: {$llm['version']}\n";
echo "   Published: " . ($llm['is_published'] ? 'YES' : 'NO') . "\n\n";

// 2. Check tools
echo "2. Checking tools...\n";
if (!isset($llm['tools'])) {
    die("❌ No tools found in LLM\n");
}

$tools = $llm['tools'];
echo "✅ Found " . count($tools) . " tools\n\n";

// 3. Fix parameter_mappings
$toolsToFix = [
    'get_current_context',
    'check_availability_v17',
    'start_booking',
    'confirm_booking'
];

$fixedCount = 0;
$alreadyCorrect = 0;
$notFound = [];

foreach ($toolsToFix as $toolName) {
    $found = false;

    foreach ($tools as &$tool) {
        if ($tool['name'] === $toolName) {
            $found = true;

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
            break;
        }
    }
    unset($tool);

    if (!$found) {
        $notFound[] = $toolName;
    }
}

echo "\n📊 Summary:\n";
echo "  - Fixed: {$fixedCount}\n";
echo "  - Already correct: {$alreadyCorrect}\n";
echo "  - Not found: " . count($notFound) . "\n";
if (count($notFound) > 0) {
    echo "    • " . implode(", ", $notFound) . "\n";
}
echo "\n";

if ($fixedCount === 0) {
    echo "✅ No changes needed!\n";
    exit(0);
}

// 4. Update LLM
echo "3. Updating LLM...\n";
$llm['tools'] = $tools;

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
    echo "Response: {$response}\n";
    echo "\n⚠️ ACTION REQUIRED: Publish manually\n";
} else {
    echo "✅ LLM V{$newVersion} published!\n\n";
}

// 6. Verify
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
echo "   Published: " . ($verifyLlm['is_published'] ? '✅ YES' : '❌ NO') . "\n";

// Check tools
foreach ($verifyLlm['tools'] as $tool) {
    if (in_array($tool['name'], $toolsToFix)) {
        $mapping = $tool['parameter_mapping']['call_id'] ?? 'MISSING';
        $status = $mapping === '{{call_id}}' ? '✅' : '❌';
        echo "   {$status} {$tool['name']}: {$mapping}\n";
    }
}

echo "\n🎉 FIX COMPLETE!\n";
echo "\nNext: Make test call to verify call_id is correct\n";

echo "\n=== END FIX ===\n";
