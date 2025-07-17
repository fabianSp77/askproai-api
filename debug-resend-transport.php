<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DEBUG ResendTransport ===\n\n";

// 1. Check ResendTransport implementation
echo "1. Checking ResendTransport:\n";
$transportFile = app_path('Mail/Transport/ResendTransport.php');
if (file_exists($transportFile)) {
    echo "   ✅ ResendTransport exists\n";
    
    // Check if send method logs anything
    $content = file_get_contents($transportFile);
    if (str_contains($content, 'Log::')) {
        echo "   ✅ Transport has logging\n";
    } else {
        echo "   ⚠️ Transport has no logging\n";
    }
} else {
    echo "   ❌ ResendTransport not found!\n";
}

// 2. Add debug logging to transport
echo "\n2. Adding debug logging to ResendTransport:\n";

// Create a test with explicit logging
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(227);
app()->instance('current_company_id', $call->company_id);

// 3. Test with debug wrapper
echo "\n3. Testing with debug wrapper:\n";

class DebugResendTransport extends \App\Mail\Transport\ResendTransport
{
    protected function doSend(\Symfony\Component\Mailer\SentMessage $message): void
    {
        echo "   [DEBUG] doSend() called\n";
        
        $email = $message->getOriginalMessage();
        $envelope = $message->getEnvelope();
        
        echo "   [DEBUG] From: " . implode(', ', array_map(fn($a) => $a->toString(), $envelope->getSender() ? [$envelope->getSender()] : $email->getFrom())) . "\n";
        echo "   [DEBUG] To: " . implode(', ', array_map(fn($a) => $a->toString(), $envelope->getRecipients())) . "\n";
        echo "   [DEBUG] Subject: " . $email->getSubject() . "\n";
        
        try {
            parent::doSend($message);
            echo "   [DEBUG] ✅ parent::doSend() completed\n";
        } catch (\Exception $e) {
            echo "   [DEBUG] ❌ parent::doSend() failed: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}

// Replace transport temporarily
$apiKey = config('services.resend.key');
$debugTransport = new DebugResendTransport($apiKey);

// Override Mail facade transport
\Illuminate\Support\Facades\Mail::purge('resend');
\Illuminate\Support\Facades\Mail::extend('resend', function() use ($debugTransport) {
    return $debugTransport;
});

// 4. Send test email
echo "\n4. Sending test email with debug transport:\n";
try {
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->send(new \App\Mail\CallSummaryEmail(
        $call,
        true,
        false, // No CSV for simplicity
        'Debug Transport Test - ' . now()->format('H:i:s'),
        'internal'
    ));
    echo "   ✅ Mail sent\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 5. Check HTTP requests
echo "\n5. Testing Resend API directly from transport:\n";
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
    'subject' => 'Direct API Test from Debug - ' . now()->format('H:i:s'),
    'html' => '<p>If this arrives, the API key and endpoint are correct.</p>'
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
echo "   Response: " . substr($response, 0, 200) . "\n";

echo "\n=== ANALYSIS ===\n";
echo "Check the debug output above to see if:\n";
echo "1. doSend() is being called\n";
echo "2. The email data is correct\n";
echo "3. Any errors occur during sending\n";