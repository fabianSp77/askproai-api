<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST Log and Direct Email ===\n\n";

// 1. Test if logging works
echo "1. Testing if logging works:\n";
\Log::info('[TEST] This is a test log entry');
echo "   ✅ Log written\n";

// 2. Check if it appears
$log = file_get_contents(storage_path('logs/laravel.log'));
if (str_contains($log, '[TEST] This is a test log entry')) {
    echo "   ✅ Log appears in file\n";
} else {
    echo "   ❌ Log does NOT appear in file\n";
}

// 3. Test direct email with same logging
echo "\n2. Testing direct email:\n";

$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(228);
app()->instance('current_company_id', $call->company_id);

try {
    \Log::info('[TEST] Before sending email');
    
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->send(new \App\Mail\CallSummaryEmail(
        $call,
        true,
        false,
        'Direct Log Test - ' . now()->format('H:i:s'),
        'internal'
    ));
    
    \Log::info('[TEST] After sending email');
    echo "   ✅ Email sent\n";
    
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 4. Check logs again
echo "\n3. Checking for all logs:\n";
$newLog = file_get_contents(storage_path('logs/laravel.log'));
$lines = explode("\n", $newLog);
$recentLines = array_slice($lines, -20);

$testLogs = 0;
$resendLogs = 0;

foreach ($recentLines as $line) {
    if (str_contains($line, '[TEST]')) {
        echo "   Found: " . substr($line, 0, 100) . "\n";
        $testLogs++;
    }
    if (str_contains($line, '[ResendTransport]')) {
        echo "   Found: " . $line . "\n";
        $resendLogs++;
    }
}

echo "\n   Test logs found: $testLogs\n";
echo "   ResendTransport logs found: $resendLogs\n";

// 5. Direct API test to ensure it's working
echo "\n4. Direct Resend API test:\n";
$apiKey = config('services.resend.key');

$ch = curl_init('https://api.resend.com/emails');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'from' => config('mail.from.address'),
    'to' => ['fabianspitzer@icloud.com'],
    'subject' => 'Direct API Test - Logs Working - ' . now()->format('H:i:s'),
    'html' => '<p>If you receive this, the API is working.</p>'
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP $httpCode\n";
if ($httpCode == 200) {
    $data = json_decode($response, true);
    echo "   ✅ Email ID: " . ($data['id'] ?? 'unknown') . "\n";
}

echo "\n=== CONCLUSION ===\n";
if ($resendLogs == 0) {
    echo "❌ ResendTransport is NOT being called at all!\n";
    echo "   The emails might be using a different driver or failing silently.\n";
} else {
    echo "✅ ResendTransport is working\n";
}