<?php

echo "🔍 RETELL SIGNATURE VERIFICATION TEST\n";
echo str_repeat("=", 50) . "\n\n";

// Test data from actual Retell webhook
$testBody = '{"event":"call_started","call":{"call_id":"test123","from_number":"+1234567890"}}';
$webhookSecret = "key_6ff998ba48e842092e04a5455d19"; // Your API key
$timestamp = time() * 1000; // Retell uses milliseconds

echo "Test Configuration:\n";
echo "- Webhook Secret: " . substr($webhookSecret, 0, 10) . "...\n";
echo "- Timestamp: $timestamp\n";
echo "- Body: $testBody\n\n";

echo "Testing different signature methods:\n\n";

// Method 1: timestamp.body with HMAC-SHA256
$payload1 = "$timestamp.$testBody";
$signature1 = hash_hmac("sha256", $payload1, $webhookSecret);
echo "1. timestamp.body (SHA256):\n";
echo "   Payload: timestamp.$testBody\n";
echo "   Signature: $signature1\n\n";

// Method 2: Just body
$signature2 = hash_hmac("sha256", $testBody, $webhookSecret);
echo "2. Body only (SHA256):\n";
echo "   Payload: $testBody\n";
echo "   Signature: $signature2\n\n";

// Method 3: Base64 encoded
$signature3 = base64_encode(hash_hmac("sha256", $payload1, $webhookSecret, true));
echo "3. Base64 encoded (timestamp.body):\n";
echo "   Signature: $signature3\n\n";

// Method 4: Retell format v=timestamp,d=signature
$retellFormat = "v=$timestamp,d=$signature1";
echo "4. Retell format:\n";
echo "   Header: X-Retell-Signature: $retellFormat\n\n";

// Method 5: Alternative format v=timestamp,signature
$retellFormat2 = "v=$timestamp,$signature1";
echo "5. Alternative Retell format:\n";
echo "   Header: X-Retell-Signature: $retellFormat2\n\n";

echo "📝 NOTES:\n";
echo "- Retell uses millisecond timestamps\n";
echo "- The signature format is: v=timestamp,d=signature\n";
echo "- The payload for signing is: timestamp.body\n";
echo "- Use HMAC-SHA256 with your API key as the secret\n\n";

// Test parsing the Retell signature format
echo "Testing signature parsing:\n";
$testHeader = "v=$timestamp,d=$signature1";
echo "Input header: $testHeader\n";

if (strpos($testHeader, "v=") === 0) {
    $headerParts = substr($testHeader, 2); // Remove v=
    $parts = explode(",", $headerParts);
    
    $parsedTimestamp = null;
    $parsedSignature = null;
    
    foreach ($parts as $part) {
        if (strpos($part, "d=") === 0) {
            $parsedSignature = substr($part, 2);
        } elseif (is_numeric($part)) {
            $parsedTimestamp = $part;
        }
    }
    
    echo "Parsed timestamp: $parsedTimestamp\n";
    echo "Parsed signature: $parsedSignature\n";
    echo "Match: " . ($parsedSignature === $signature1 ? "✅ YES" : "❌ NO") . "\n";
}
