<?php

// Test Cal.com connection directly

$apiKey = 'cal_live_bd7aedbdf12085c5312c79ba73585920';
$teamSlug = 'askproai';

echo "=== Testing Cal.com V2 API Directly ===\n\n";

// 1. Test getting event types
echo "1. Getting Event Types...\n";
// V2 API uses different endpoint structure
$ch = curl_init("https://api.cal.com/api/v2/event-types?teamId=$teamSlug");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json",
    "cal-api-version: 2024-08-13"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "✅ Success! Found " . count($data['data'] ?? []) . " event types\n";
    
    foreach (($data['data'] ?? []) as $et) {
        echo "  - ID: " . $et['id'] . " | Title: " . $et['title'] . " | Slug: " . $et['slug'] . "\n";
    }
} else {
    echo "❌ Failed!\n";
    echo "Response: " . substr($response, 0, 500) . "\n";
}

// 2. Test getting specific event type
echo "\n2. Getting Event Type 2026361...\n";
$ch = curl_init("https://api.cal.com/api/v2/event-types/2026361");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json",
    "cal-api-version: 2024-08-13"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "✅ Event Type found!\n";
    echo "  - Title: " . ($data['data']['title'] ?? 'N/A') . "\n";
    echo "  - Duration: " . ($data['data']['length'] ?? 'N/A') . " minutes\n";
    echo "  - Active: " . (($data['data']['hidden'] ?? false) ? 'No' : 'Yes') . "\n";
} else {
    echo "❌ Event Type not found!\n";
    echo "Response: " . substr($response, 0, 500) . "\n";
}

// 3. Test getting available slots
echo "\n3. Getting Available Slots...\n";
$startDate = date('Y-m-d');
$endDate = date('Y-m-d', strtotime('+7 days'));

$slotsUrl = "https://api.cal.com/api/v2/slots/available?" . http_build_query([
    'eventTypeId' => 2026361,
    'startTime' => $startDate,
    'endTime' => $endDate,
    'timeZone' => 'Europe/Berlin'
]);

$ch = curl_init($slotsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json",
    "cal-api-version: 2024-08-13"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($httpCode === 200) {
    $data = json_decode($response, true);
    $slots = $data['data']['slots'] ?? [];
    echo "✅ Found " . count($slots) . " available slots\n";
    
    // Show first 5 slots
    foreach (array_slice($slots, 0, 5) as $slot) {
        echo "  - " . $slot['time'] . "\n";
    }
} else {
    echo "❌ Failed to get slots!\n";
    echo "Response: " . substr($response, 0, 500) . "\n";
}