<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;
use App\Models\Call;
use App\Mail\CallSummaryEmail;

echo "Testing email system...\n";
echo "Mail driver: " . config('mail.default') . "\n";
echo "SMTP host: " . config('mail.mailers.smtp.host') . "\n";
echo "SMTP port: " . config('mail.mailers.smtp.port') . "\n";
echo "SMTP username: " . config('mail.mailers.smtp.username') . "\n";
echo "From address: " . config('mail.from.address') . "\n\n";

// Get a test call
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(229);

if (!$call) {
    echo "Call 229 not found!\n";
    exit(1);
}

echo "Using call ID: {$call->id}\n";
echo "Call from: {$call->from_number}\n\n";

// Test direct send
try {
    echo "Attempting to send test email...\n";
    
    Mail::raw('Dies ist eine Test-E-Mail vom AskProAI System.', function ($message) {
        $message->to('stephan@boehm-software.de')
                ->subject('Test E-Mail - ' . now()->format('d.m.Y H:i:s'));
    });
    
    echo "✅ Test email sent successfully!\n\n";
} catch (\Exception $e) {
    echo "❌ Error sending test email: " . $e->getMessage() . "\n";
    echo "Error type: " . get_class($e) . "\n\n";
    
    if (strpos($e->getMessage(), 'Failed to authenticate') !== false) {
        echo "⚠️  SMTP authentication failed. Checking credentials...\n";
        echo "Username: " . config('mail.mailers.smtp.username') . "\n";
        echo "Password length: " . strlen(config('mail.mailers.smtp.password')) . " characters\n";
    }
}

// Test queued email
try {
    echo "Attempting to queue call summary email...\n";
    
    Mail::to('stephan@boehm-software.de')->queue(new CallSummaryEmail(
        $call,
        true,  // include transcript
        false, // include CSV
        'Test-Nachricht vom System',
        'internal'
    ));
    
    echo "✅ Call summary email queued successfully!\n";
    echo "Check queue with: php artisan queue:work --queue=default --tries=1\n";
} catch (\Exception $e) {
    echo "❌ Error queuing email: " . $e->getMessage() . "\n";
}