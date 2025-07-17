<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;

echo "=== DIRECT MAIL TEST ===\n\n";

// Get call for testing
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(258);
app()->instance('current_company_id', $call->company_id);

echo "1. Mail configuration:\n";
echo "Default mailer: " . config('mail.default') . "\n";
echo "Resend key: " . (config('mail.mailers.resend.key') ? 'SET' : 'NOT SET') . "\n";
echo "From address: " . config('mail.from.address') . "\n";

echo "\n2. Testing direct Mail::send:\n";
try {
    // Enable debug mode
    config(['mail.mailers.resend.log' => true]);
    
    // Create mail
    $email = new \App\Mail\CallSummaryEmail(
        $call,
        true,
        true,  
        'Direct mail test - ' . now()->format('H:i:s'),
        'internal'
    );
    
    // Send
    Mail::to('fabianspitzer@icloud.com')->send($email);
    echo "✅ Mail::send completed\n";
    
    // Check failures
    $failures = Mail::failures();
    if (!empty($failures)) {
        echo "Failed recipients: " . implode(', ', $failures) . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

// Check transport
echo "\n3. Checking mail transport:\n";
$transport = Mail::mailer('resend')->getSymfonyTransport();
echo "Transport class: " . get_class($transport) . "\n";

// Test raw mail
echo "\n4. Testing raw mail:\n";
try {
    Mail::raw('Test email body', function ($message) {
        $message->to('test@example.com')
                ->subject('Raw test - ' . now()->format('H:i:s'));
    });
    echo "✅ Raw mail sent\n";
} catch (\Exception $e) {
    echo "❌ Raw mail error: " . $e->getMessage() . "\n";
}

// Force check logs
echo "\n5. Force checking all logs:\n";
$timestamp = now()->format('H:i');
$logs = shell_exec("grep '$timestamp' /var/www/api-gateway/storage/logs/laravel-2025-07-08.log | grep -E 'ResendTransport|Mail|Email' | tail -10");
echo $logs ?: "No logs found for $timestamp\n";