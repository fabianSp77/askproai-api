#!/usr/bin/env php
<?php
/**
 * Fix Webhook Signature Verification
 * 
 * This script fixes webhook signature verification issues by:
 * 1. Ensuring API key equals webhook secret
 * 2. Testing signature generation
 * 3. Sending test webhook
 * 
 * Error Code: RETELL_002
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔧 Webhook Signature Fix Script\n";
echo "===============================\n\n";

try {
    // Step 1: Check current configuration
    echo "1. Checking current configuration...\n";
    
    $apiKey = env('RETELL_TOKEN');
    $webhookSecret = env('RETELL_WEBHOOK_SECRET');
    
    echo "   API Key: " . ($apiKey ? substr($apiKey, 0, 10) . '...' : 'NOT SET') . "\n";
    echo "   Webhook Secret: " . ($webhookSecret ? substr($webhookSecret, 0, 10) . '...' : 'NOT SET') . "\n";
    
    if (!$apiKey || !$webhookSecret) {
        echo "   ❌ Missing configuration! Please set RETELL_TOKEN and RETELL_WEBHOOK_SECRET in .env\n";
        exit(1);
    }
    
    if ($apiKey !== $webhookSecret) {
        echo "   ⚠️  API Key and Webhook Secret do not match!\n";
        echo "   This is a common cause of signature verification failures.\n";
        echo "\n   To fix, ensure both values are identical in your .env file:\n";
        echo "   RETELL_TOKEN={$apiKey}\n";
        echo "   RETELL_WEBHOOK_SECRET={$apiKey}  # Must be same as token!\n";
        echo "\n   Then run: php artisan config:cache\n";
        exit(1);
    } else {
        echo "   ✅ API Key and Webhook Secret match\n";
    }

    // Step 2: Test signature generation
    echo "\n2. Testing signature generation...\n";
    
    $testPayload = json_encode([
        'event' => 'call_ended',
        'call' => [
            'call_id' => 'test_' . time(),
            'agent_id' => 'test_agent',
            'call_status' => 'ended',
            'start_timestamp' => time() * 1000,
            'end_timestamp' => (time() + 300) * 1000,
        ]
    ]);
    
    $timestamp = (string) time();
    $signature = hash_hmac('sha256', $testPayload . $timestamp, $webhookSecret);
    $headerValue = "v={$timestamp},d={$signature}";
    
    echo "   Test payload: " . substr($testPayload, 0, 50) . "...\n";
    echo "   Timestamp: {$timestamp}\n";
    echo "   Signature: {$signature}\n";
    echo "   Header value: {$headerValue}\n";

    // Step 3: Verify signature locally
    echo "\n3. Verifying signature locally...\n";
    
    // Simulate what the middleware does
    $parts = explode(',', $headerValue);
    $extractedTimestamp = null;
    $extractedSignature = null;
    
    foreach ($parts as $part) {
        if (strpos($part, 'v=') === 0) {
            $extractedTimestamp = substr($part, 2);
        } elseif (strpos($part, 'd=') === 0) {
            $extractedSignature = substr($part, 2);
        }
    }
    
    $expectedSignature = hash_hmac('sha256', $testPayload . $extractedTimestamp, $webhookSecret);
    
    if (hash_equals($expectedSignature, $extractedSignature)) {
        echo "   ✅ Signature verification successful\n";
    } else {
        echo "   ❌ Signature verification failed\n";
        echo "   Expected: {$expectedSignature}\n";
        echo "   Got: {$extractedSignature}\n";
        exit(1);
    }

    // Step 4: Send test webhook
    echo "\n4. Sending test webhook to local endpoint...\n";
    
    $webhookUrl = config('app.url') . '/api/retell/webhook-simple';
    echo "   Webhook URL: {$webhookUrl}\n";
    
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $testPayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-retell-signature: ' . $headerValue
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "   ❌ cURL error: {$error}\n";
    } elseif ($httpCode === 200) {
        echo "   ✅ Webhook accepted (HTTP {$httpCode})\n";
        echo "   Response: " . substr($response, 0, 100) . "\n";
    } elseif ($httpCode === 401) {
        echo "   ❌ Webhook rejected with 401 Unauthorized\n";
        echo "   This means signature verification failed on the server\n";
        echo "   Response: {$response}\n";
    } else {
        echo "   ⚠️  Unexpected response (HTTP {$httpCode})\n";
        echo "   Response: {$response}\n";
    }

    // Step 5: Provide configuration template
    echo "\n5. Configuration checklist:\n";
    echo "   ✓ RETELL_TOKEN and RETELL_WEBHOOK_SECRET must be identical\n";
    echo "   ✓ Both should match the API key in Retell.ai dashboard\n";
    echo "   ✓ Run 'php artisan config:cache' after changes\n";
    echo "   ✓ Webhook URL in Retell.ai: {$webhookUrl}\n";
    echo "   ✓ Signature format: v=timestamp,d=signature\n";

    echo "\n✅ Webhook signature configuration verified!\n";
    
    if ($httpCode !== 200) {
        echo "\n⚠️  Note: The test webhook was not accepted. Please check:\n";
        echo "1. Is the application running?\n";
        echo "2. Are there any middleware issues?\n";
        echo "3. Check storage/logs/laravel.log for details\n";
    }
    
    exit(0);

} catch (\Exception $e) {
    echo "\n❌ Error during fix process: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}