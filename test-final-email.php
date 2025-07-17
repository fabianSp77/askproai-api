<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;

echo "=== FINAL EMAIL TEST ===\n\n";

// Check config
echo "1. Configuration check:\n";
echo "Resend key from config: " . (config('mail.mailers.resend.key') ? 'SET' : 'NOT SET') . "\n";
echo "Resend key value: " . substr(config('mail.mailers.resend.key'), 0, 10) . "...\n";

// Test direct send
echo "\n2. Testing direct send to Fabian:\n";
try {
    $call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(258);
    app()->instance('current_company_id', $call->company_id);
    
    $email = new \App\Mail\CallSummaryEmail(
        $call,
        true,
        true,
        'FINALER TEST - Config gefixt - ' . now()->format('H:i:s'),
        'internal'
    );
    
    Mail::to('fabianspitzer@icloud.com')->send($email);
    echo "✅ Email sent!\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Check logs
echo "\n3. Checking ResendTransport logs:\n";
$logs = shell_exec("tail -50 /var/www/api-gateway/storage/logs/laravel-2025-07-08.log | grep ResendTransport | tail -5");
echo $logs ?: "No logs found\n";

// Test via job
echo "\n4. Testing via job dispatch:\n";
\App\Jobs\SendCallSummaryEmailJob::dispatch(
    258,
    ['fabianspitzer@icloud.com'],
    true,
    true,
    'Job test nach Config Fix - ' . now()->format('H:i:s'),
    'internal'
);
echo "✅ Job dispatched\n";

// Wait and check
sleep(5);
echo "\n5. Final log check:\n";
$finalLogs = shell_exec("tail -20 /var/www/api-gateway/storage/logs/laravel-2025-07-08.log | grep -E 'ResendTransport|SendCallSummaryEmailJob' | tail -10");
echo $finalLogs ?: "No recent logs\n";

echo "\n=== EMAILS SHOULD NOW BE ARRIVING! ===\n";