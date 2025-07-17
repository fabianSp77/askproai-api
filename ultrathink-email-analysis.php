<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

echo "=== ULTRATHINK EMAIL ANALYSIS ===\n";
echo "Zeitpunkt: " . now()->format('Y-m-d H:i:s') . "\n\n";

// 1. CONFIG VERIFICATION
echo "1. KONFIGURATION:\n";
echo "   MAIL_MAILER: " . env('MAIL_MAILER') . "\n";
echo "   RESEND_API_KEY exists: " . (env('RESEND_API_KEY') ? 'YES' : 'NO') . "\n";
echo "   Config mail.default: " . config('mail.default') . "\n";
echo "   Config mail.mailers.resend.key: " . (config('mail.mailers.resend.key') ? 'SET' : 'NOT SET') . "\n";
echo "   Config services.resend.key: " . (config('services.resend.key') ? 'SET' : 'NOT SET') . "\n";

// 2. TRANSPORT CHECK
echo "\n2. TRANSPORT PRÜFUNG:\n";
try {
    $transport = Mail::mailer('resend')->getSymfonyTransport();
    echo "   Transport Klasse: " . get_class($transport) . "\n";
    
    // Check if transport has API key
    $reflection = new ReflectionClass($transport);
    $apiKeyProperty = $reflection->getProperty('apiKey');
    $apiKeyProperty->setAccessible(true);
    $apiKey = $apiKeyProperty->getValue($transport);
    echo "   Transport hat API Key: " . ($apiKey ? 'YES (' . substr($apiKey, 0, 10) . '...)' : 'NO') . "\n";
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// 3. TEST RESEND API DIRECTLY
echo "\n3. DIREKTER RESEND API TEST:\n";
$apiKey = config('services.resend.key') ?: config('mail.mailers.resend.key') ?: env('RESEND_API_KEY');
if ($apiKey) {
    echo "   Using API Key: " . substr($apiKey, 0, 10) . "...\n";
    
    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'from' => 'AskProAI <info@askproai.de>',
            'to' => ['test-' . time() . '@askproai.de'],
            'subject' => 'API Test - ' . now()->format('H:i:s'),
            'html' => '<p>Direct API test</p>'
        ]),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 10,
    ]);
    
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "   Status Code: $statusCode\n";
    echo "   Response: " . substr($response, 0, 200) . "\n";
} else {
    echo "   ERROR: No API key found!\n";
}

// 4. LOGGING CONFIGURATION
echo "\n4. LOGGING KONFIGURATION:\n";
echo "   Default log channel: " . config('logging.default') . "\n";
echo "   Daily log level: " . config('logging.channels.daily.level') . "\n";
echo "   Stack channels: " . implode(', ', config('logging.channels.stack.channels', [])) . "\n";

// 5. TEST WITH DIFFERENT LOG LEVELS
echo "\n5. LOG LEVEL TEST:\n";
Log::debug('[ULTRATHINK] Debug message');
Log::info('[ULTRATHINK] Info message');
Log::warning('[ULTRATHINK] Warning message');
Log::error('[ULTRATHINK] Error message');

// Check which ones appear
$logFile = storage_path('logs/laravel-' . now()->format('Y-m-d') . '.log');
$logs = shell_exec("tail -20 '$logFile' | grep ULTRATHINK");
echo "   Gefundene Logs:\n$logs";

// 6. QUEUE & HORIZON CHECK
echo "\n6. QUEUE & HORIZON STATUS:\n";
$redis = app('redis');
$emailQueue = $redis->llen('queues:emails');
echo "   Jobs in email queue: $emailQueue\n";

// Check Horizon status
$horizonRunning = shell_exec("ps aux | grep 'horizon' | grep -v grep | wc -l");
echo "   Horizon Prozesse: " . trim($horizonRunning) . "\n";

// Check email workers specifically
$emailWorkers = shell_exec("ps aux | grep 'queue:work.*emails' | grep -v grep | wc -l");
echo "   Email Worker: " . trim($emailWorkers) . "\n";

// 7. TEST EMAIL WITH FORCED LOGGING
echo "\n7. EMAIL MIT ERZWUNGENEM LOGGING:\n";
try {
    // Override log channel temporarily
    config(['mail.mailers.resend.log_channel' => 'single']);
    config(['logging.channels.single.level' => 'debug']);
    
    $call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(258);
    app()->instance('current_company_id', $call->company_id);
    
    // Log before sending
    Log::channel('single')->error('[ULTRATHINK] Before sending email');
    
    $email = new \App\Mail\CallSummaryEmail(
        $call,
        true,
        true,
        'ULTRATHINK Test - ' . now()->format('H:i:s'),
        'internal'
    );
    
    Mail::to('fabianspitzer@icloud.com')->send($email);
    
    // Log after sending
    Log::channel('single')->error('[ULTRATHINK] After sending email');
    
    echo "   ✅ Email send completed\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    Log::channel('single')->error('[ULTRATHINK] Email error', ['error' => $e->getMessage()]);
}

// 8. CHECK ALL LOG FILES
echo "\n8. ALLE LOG DATEIEN PRÜFEN:\n";
$pattern = now()->format('H:i');
$logFiles = glob(storage_path('logs/*.log'));
foreach ($logFiles as $file) {
    $found = shell_exec("grep -l '$pattern' '$file' 2>/dev/null");
    if ($found) {
        echo "   " . basename($file) . " - hat Einträge von $pattern\n";
        $content = shell_exec("grep '$pattern' '$file' | grep -E 'Resend|Email|Mail' | tail -3");
        if ($content) {
            echo "   Inhalt:\n$content\n";
        }
    }
}

// 9. RESEND DASHBOARD CHECK
echo "\n9. RESEND DASHBOARD STATUS:\n";
$ch = curl_init('https://api.resend.com/emails?limit=5');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
    ],
]);
$response = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($statusCode === 200) {
    $data = json_decode($response, true);
    if (isset($data['data']) && is_array($data['data'])) {
        echo "   Letzte 5 E-Mails in Resend:\n";
        foreach ($data['data'] as $email) {
            echo "   - " . $email['created_at'] . ": " . $email['to'][0] . " - " . $email['subject'] . "\n";
        }
    }
} else {
    echo "   Could not fetch Resend emails. Status: $statusCode\n";
}

// 10. FINAL DIAGNOSIS
echo "\n10. DIAGNOSE:\n";
$issues = [];

if (!$apiKey) {
    $issues[] = "Kein API Key gefunden";
}

if ($emailQueue > 0) {
    $issues[] = "Es sind $emailQueue Jobs in der Queue - werden sie verarbeitet?";
}

if (trim($emailWorkers) == "0") {
    $issues[] = "Keine Email Worker laufen";
}

if (config('logging.channels.daily.level') === 'error') {
    $issues[] = "Log Level ist auf 'error' - INFO logs werden nicht geschrieben";
}

if (empty($issues)) {
    echo "   Keine offensichtlichen Probleme gefunden\n";
} else {
    foreach ($issues as $issue) {
        echo "   ❌ $issue\n";
    }
}

echo "\n=== ENDE ULTRATHINK ANALYSE ===\n";