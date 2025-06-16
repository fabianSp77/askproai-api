<?php
/**
 * Simple script to test Cal.com API key compatibility
 * Run: php test-calcom-api-key.php
 */

$apiKey = 'cal_live_e9aa2c4d18e0fd79cf4f8dddb90903da';

echo "\n========================================\n";
echo "Cal.com API Key Compatibility Test\n";
echo "========================================\n\n";
echo "API Key: " . substr($apiKey, 0, 20) . "...\n\n";

// Test V1 API
echo "Testing V1 API...\n";
echo "-----------------\n";

// V1 with query parameter
$v1QueryUrl = "https://api.cal.com/v1/event-types?apiKey={$apiKey}";
$ch = curl_init($v1QueryUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$v1QueryResponse = curl_exec($ch);
$v1QueryCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "V1 Query Parameter Method: ";
if ($v1QueryCode === 200) {
    echo "✅ SUCCESS (HTTP {$v1QueryCode})\n";
    $data = json_decode($v1QueryResponse, true);
    if (isset($data['event_types'])) {
        echo "   Found " . count($data['event_types']) . " event types\n";
    }
} else {
    echo "❌ FAILED (HTTP {$v1QueryCode})\n";
    if ($v1QueryCode === 403) {
        echo "   Access forbidden - API key not authorized for V1\n";
    }
}

// V1 with Bearer header (some keys might work)
$ch = curl_init("https://api.cal.com/v1/event-types");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$v1BearerResponse = curl_exec($ch);
$v1BearerCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "V1 Bearer Token Method: ";
if ($v1BearerCode === 200) {
    echo "✅ SUCCESS (HTTP {$v1BearerCode})\n";
} else {
    echo "❌ FAILED (HTTP {$v1BearerCode})\n";
}

echo "\n";

// Test V2 API
echo "Testing V2 API...\n";
echo "-----------------\n";

// V2 with Bearer header
$ch = curl_init("https://api.cal.com/v2/event-types");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "cal-api-version: 2024-08-13",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$v2Response = curl_exec($ch);
$v2Code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "V2 Bearer Token Method: ";
if ($v2Code === 200) {
    echo "✅ SUCCESS (HTTP {$v2Code})\n";
    $data = json_decode($v2Response, true);
    if (isset($data['data'])) {
        echo "   Response has 'data' wrapper (V2 format confirmed)\n";
        if (is_array($data['data'])) {
            echo "   Found " . count($data['data']) . " event types\n";
        }
    }
} else {
    echo "❌ FAILED (HTTP {$v2Code})\n";
}

// Test V2 slots endpoint
$ch = curl_init("https://api.cal.com/v2/slots/available?eventTypeId=2026302&startTime=2024-06-15T00:00:00Z&endTime=2024-06-15T23:59:59Z&timeZone=Europe/Berlin");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "cal-api-version: 2024-08-13",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$v2SlotsResponse = curl_exec($ch);
$v2SlotsCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "V2 Slots Endpoint: ";
if ($v2SlotsCode === 200) {
    echo "✅ SUCCESS (HTTP {$v2SlotsCode})\n";
} else {
    echo "❌ FAILED (HTTP {$v2SlotsCode})\n";
}

echo "\n";

// Summary and Recommendations
echo "========================================\n";
echo "Summary & Recommendations\n";
echo "========================================\n\n";

$v1Works = $v1QueryCode === 200 || $v1BearerCode === 200;
$v2Works = $v2Code === 200;

if ($v2Works && !$v1Works) {
    echo "✅ Your API key is V2-ONLY\n\n";
    echo "Recommended configuration:\n";
    echo "```\n";
    echo "CALCOM_API_KEY={$apiKey}\n";
    echo "CALCOM_API_VERSION=v2\n";
    echo "CALCOM_V2_API_VERSION=2024-08-13\n";
    echo "CALCOM_FALLBACK_V1=false\n";
    echo "```\n\n";
    echo "Action items:\n";
    echo "1. Update your .env file with the above configuration\n";
    echo "2. Use CalcomUnifiedService with v2 configuration\n";
    echo "3. Do not enable v1 fallback as it won't work\n";
} elseif ($v1Works && !$v2Works) {
    echo "✅ Your API key is V1-ONLY\n\n";
    echo "Recommended configuration:\n";
    echo "```\n";
    echo "CALCOM_API_KEY={$apiKey}\n";
    echo "CALCOM_API_VERSION=v1\n";
    echo "CALCOM_FALLBACK_V1=false\n";
    echo "```\n\n";
    echo "⚠️  Warning: V1 API is deprecated. Consider getting a new V2 API key.\n";
} elseif ($v1Works && $v2Works) {
    echo "✅ Your API key works with BOTH V1 and V2\n\n";
    echo "Recommended configuration:\n";
    echo "```\n";
    echo "CALCOM_API_KEY={$apiKey}\n";
    echo "CALCOM_API_VERSION=v2\n";
    echo "CALCOM_V2_API_VERSION=2024-08-13\n";
    echo "CALCOM_FALLBACK_V1=true\n";
    echo "```\n\n";
    echo "You have the best compatibility. Use V2 with V1 fallback enabled.\n";
} else {
    echo "❌ Your API key doesn't work with either V1 or V2\n\n";
    echo "Possible issues:\n";
    echo "1. Invalid API key\n";
    echo "2. API key is disabled\n";
    echo "3. Network connectivity issues\n";
    echo "4. Rate limiting\n\n";
    echo "Please check your Cal.com account and generate a new API key.\n";
}

echo "\n";