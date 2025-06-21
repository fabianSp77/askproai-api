<?php

// Analyze actual Retell webhook signature from logs
$webhookSecret = 'key_6ff998ba48e842092e04a5455d19';

// Example webhook data from logs
$webhookData = [
    "event" => "call_ended",
    "call" => [
        "call_id" => "c415a9facdf5873ab8d20db11b8860e31d15f75ed2b36816c3dd231f65bf0416",
        "agent_id" => "agent_9a8202a740cd3120d96fcfda1e",
        "from_number" => "+491604366218",
        "to_number" => "+493083793369",
        "call_status" => "ended",
        "start_timestamp" => 1732894725000,
        "end_timestamp" => 1732894809000,
        "duration_ms" => 84000,
        "transcript" => "Hallo, hier ist die AskProAI..."
    ]
];

$jsonPayload = json_encode($webhookData);

echo "Analyzing Retell Webhook Signature...\n\n";

// Test different signature calculation methods
$timestamp = "1750282985"; // From logs

echo "Testing signature calculation methods:\n";
echo "Webhook Secret: " . substr($webhookSecret, 0, 10) . "...\n";
echo "Timestamp: $timestamp\n\n";

// Method 1: timestamp.payload (what our code expects)
$payload1 = $timestamp . '.' . $jsonPayload;
$sig1 = hash_hmac('sha256', $payload1, $webhookSecret);
echo "1. Our expected (timestamp.payload): " . substr($sig1, 0, 20) . "...\n";

// Method 2: Just payload (no timestamp)
$sig2 = hash_hmac('sha256', $jsonPayload, $webhookSecret);
echo "2. Just payload: " . substr($sig2, 0, 20) . "...\n";

// Method 3: Timestamp in headers but not in signature
$sig3 = hash_hmac('sha256', $jsonPayload, $webhookSecret);
echo "3. Payload only (timestamp in header): " . substr($sig3, 0, 20) . "...\n";

// From logs:
// Expected: "94472cbde0..."
// Received: "71debf50f7..."

echo "\nFrom logs:\n";
echo "Expected by our code: 94472cbde0...\n";
echo "Received from Retell: 71debf50f7...\n";

// Try to figure out what Retell is actually signing
echo "\nTrying to reverse engineer Retell's signature...\n";

// Method 4: Maybe Retell uses a different timestamp format
$timestampMs = $timestamp . "000"; // Convert to milliseconds
$payload4 = $timestampMs . '.' . $jsonPayload;
$sig4 = hash_hmac('sha256', $payload4, $webhookSecret);
echo "4. With ms timestamp: " . substr($sig4, 0, 20) . "...\n";

// Method 5: Maybe without dots
$payload5 = $timestamp . $jsonPayload;
$sig5 = hash_hmac('sha256', $payload5, $webhookSecret);
echo "5. Timestamp+payload (no dot): " . substr($sig5, 0, 20) . "...\n";

echo "\nConclusion: We need to adjust our signature verification to match Retell's format.\n";