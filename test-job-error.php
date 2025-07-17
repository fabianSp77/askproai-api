<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TESTING JOB FOR ERRORS ===\n\n";

$job = new \App\Jobs\SendCallSummaryEmailJob(
    258,
    ['fabianspitzer@icloud.com'],
    true,
    true,
    'Error test - ' . now()->format('H:i:s'),
    'internal'
);

echo "1. Testing job handle method directly:\n";
try {
    $job->handle();
    echo "✅ Job handle() completed without exception\n";
} catch (\Throwable $e) {
    echo "❌ Exception in handle(): " . $e->getMessage() . "\n";
    echo "Type: " . get_class($e) . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

// Check if email was logged to mail channel
echo "\n2. Checking mail logs:\n";
$mailLog = shell_exec("tail -20 /var/www/api-gateway/storage/logs/mail-*.log 2>/dev/null | grep -v 'tail:'");
echo $mailLog ?: "No mail logs\n";

// Check Resend logs in all possible locations
echo "\n3. Searching for Resend logs:\n";
$resendLogs = shell_exec("find /var/www/api-gateway/storage/logs -name '*.log' -type f -exec grep -l 'ResendTransport' {} \\; 2>/dev/null");
echo $resendLogs ?: "No files contain ResendTransport logs\n";

// Check if there's a horizon log
echo "\n4. Checking Horizon logs:\n";
$horizonLog = shell_exec("tail -20 /var/www/api-gateway/storage/logs/horizon.log 2>/dev/null | grep -E 'SendCallSummaryEmailJob|email' -i");
echo $horizonLog ?: "No relevant Horizon logs\n";

// Force a log write
echo "\n5. Force writing to log:\n";
$logFile = storage_path('logs/forced-test.log');
file_put_contents($logFile, "[" . now() . "] Job test executed\n", FILE_APPEND);
if (file_exists($logFile)) {
    echo "✅ Can write to logs directory\n";
    echo "Contents: " . file_get_contents($logFile);
} else {
    echo "❌ Cannot write to logs directory\n";
}