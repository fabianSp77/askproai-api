<?php

/**
 * Analysiert fehlgeschlagene Retell.ai Webhook-Versuche
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Retell.ai Webhook Failure Analysis ===\n\n";

// Suche nach Webhook-Events in der Datenbank
$recentWebhooks = DB::table('webhook_events')
    ->where('provider', 'retell')
    ->where('created_at', '>=', now()->subHours(24))
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

echo "Found " . $recentWebhooks->count() . " Retell webhooks in the last 24 hours\n\n";

foreach ($recentWebhooks as $webhook) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Webhook ID: {$webhook->id}\n";
    echo "Created: {$webhook->created_at}\n";
    echo "Status: {$webhook->status}\n";
    echo "Event Type: {$webhook->event_type}\n";
    
    // Decode headers
    $headers = json_decode($webhook->headers, true);
    $signature = $headers['x-retell-signature'] ?? $headers['X-Retell-Signature'] ?? null;
    $timestamp = $headers['x-retell-timestamp'] ?? $headers['X-Retell-Timestamp'] ?? null;
    
    echo "\nHeaders:\n";
    echo "  X-Retell-Signature: " . ($signature ? substr($signature[0] ?? $signature, 0, 50) . '...' : 'NOT FOUND') . "\n";
    echo "  X-Retell-Timestamp: " . ($timestamp ? $timestamp[0] ?? $timestamp : 'NOT FOUND') . "\n";
    
    // Decode payload
    $payload = json_decode($webhook->payload, true);
    $callId = $payload['call_id'] ?? $payload['call']['call_id'] ?? 'unknown';
    
    echo "\nPayload Summary:\n";
    echo "  Call ID: $callId\n";
    echo "  From: " . ($payload['from_number'] ?? $payload['call']['from_number'] ?? 'N/A') . "\n";
    echo "  To: " . ($payload['to_number'] ?? $payload['call']['to_number'] ?? 'N/A') . "\n";
    
    // Versuche die Signatur zu verifizieren
    if ($signature && $webhook->payload) {
        echo "\nSignature Verification Test:\n";
        
        $apiKey = config('services.retell.api_key');
        $webhookSecret = config('services.retell.webhook_secret');
        
        // Parse signature
        $sig = is_array($signature) ? $signature[0] : $signature;
        $parsedTimestamp = null;
        $parsedSignature = null;
        
        if (preg_match('/v=(\d+),d=([a-f0-9]+)/', $sig, $matches)) {
            $parsedTimestamp = $matches[1];
            $parsedSignature = $matches[2];
        }
        
        if ($parsedTimestamp && $parsedSignature) {
            // Test verschiedene Methoden
            $methods = [
                'API Key - concat' => hash_hmac('sha256', $parsedTimestamp . $webhook->payload, $apiKey),
                'API Key - dot' => hash_hmac('sha256', $parsedTimestamp . '.' . $webhook->payload, $apiKey),
                'Webhook Secret - concat' => hash_hmac('sha256', $parsedTimestamp . $webhook->payload, $webhookSecret),
                'Webhook Secret - dot' => hash_hmac('sha256', $parsedTimestamp . '.' . $webhook->payload, $webhookSecret),
            ];
            
            foreach ($methods as $method => $calculated) {
                $match = hash_equals($calculated, $parsedSignature);
                echo "  $method: " . ($match ? '✅ MATCH!' : '❌ No match') . "\n";
                if (!$match) {
                    echo "    Expected: " . substr($calculated, 0, 20) . "...\n";
                    echo "    Received: " . substr($parsedSignature, 0, 20) . "...\n";
                }
            }
        } else {
            echo "  Could not parse signature format\n";
        }
    }
    
    // Error details
    if ($webhook->error) {
        echo "\nError Details:\n";
        echo "  " . str_replace("\n", "\n  ", $webhook->error) . "\n";
    }
}

// Prüfe Logs
echo "\n\n=== Recent Log Entries ===\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logs = shell_exec("grep -A5 -B5 'Retell.*signature.*failed' $logFile | tail -n 50");
    if ($logs) {
        echo $logs;
    } else {
        echo "No signature failure logs found.\n";
    }
}

// Empfehlungen
echo "\n=== Recommendations ===\n";
echo "1. If all webhooks are failing signature verification, the secret might be wrong\n";
echo "2. Check if Retell changed their signature format recently\n";
echo "3. Consider contacting Retell support if the issue persists\n";
echo "4. Temporarily add more logging to capture the exact signature format\n";