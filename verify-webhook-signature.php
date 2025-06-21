<?php

// Verify signature from actual webhook log

$apiKey = 'key_6ff998ba48e842092e04a5455d19';

// Data from webhook log ID 1
$timestamp = '1750282926';
$signature = 'bf65a0d8385d5737c671b1e3dec15d6153cdaf39d9f1a0cd91b4325ffdf0fbb7';
$payload = '{"event":"call_ended","call":{"call_id":"8fe67ef8-3cd7-37cc-4e6b-96be08d12345","agent_id":"agent_9a8202a740cd3120d96fcfda1e","call_type":"inbound","call_status":"ended","start_timestamp":1750282806000,"end_timestamp":1750282926000,"duration_ms":120000,"from_number":"+4915234567890","to_number":"+493083793369","transcript":"Test call","transcript_object":[],"cost":0.25},"webhook_validated":true,"webhook_validation_skipped":true}';

echo "Verifying webhook signature from logs...\n\n";

// Test different signature formats
echo "Testing timestamp.payload format:\n";
$signaturePayload1 = $timestamp . '.' . $payload;
$expectedSig1 = hash_hmac('sha256', $signaturePayload1, $apiKey);
echo "Expected: $expectedSig1\n";
echo "Actual:   $signature\n";
echo "Match: " . ($expectedSig1 === $signature ? "YES ✅" : "NO ❌") . "\n\n";

echo "Testing just payload:\n";
$expectedSig2 = hash_hmac('sha256', $payload, $apiKey);
echo "Expected: $expectedSig2\n";
echo "Actual:   $signature\n";
echo "Match: " . ($expectedSig2 === $signature ? "YES ✅" : "NO ❌") . "\n\n";

// Try with different keys
$possibleKeys = [
    'key_6ff998ba48e842092e04a5455d19' => 'Current API key',
    'key_6ff998a93c40f83f2bec9d25343f' => 'Alternative API key from .env',
];

foreach ($possibleKeys as $key => $description) {
    echo "Testing with $description ($key):\n";
    
    $sig1 = hash_hmac('sha256', $signaturePayload1, $key);
    $sig2 = hash_hmac('sha256', $payload, $key);
    
    if ($sig1 === $signature) {
        echo "✅ MATCH with timestamp.payload format!\n";
        echo "Secret key: $key\n";
        break;
    } elseif ($sig2 === $signature) {
        echo "✅ MATCH with payload only format!\n";
        echo "Secret key: $key\n";
        break;
    } else {
        echo "❌ No match\n";
    }
    echo "\n";
}