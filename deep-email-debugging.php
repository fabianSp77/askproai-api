<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DEEP E-Mail Debugging ===\n\n";

// 1. Check if emails are actually being sent to Resend
echo "1. Checking recent Resend API calls:\n";

$apiKey = config('services.resend.key');

// Get email by ID from earlier test
$emailIds = [
    '6999933d-3c27-4143-a1a9-ce7c0e802bb4',
    'c92c80b8-74fd-4bd3-97c8-af01fc98155c'
];

foreach ($emailIds as $emailId) {
    $ch = curl_init("https://api.resend.com/emails/{$emailId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "\n   Email ID: $emailId\n";
    echo "   HTTP Code: $httpCode\n";
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        echo "   Status: " . ($data['last_event'] ?? 'N/A') . "\n";
        echo "   To: " . ($data['to'][0] ?? 'N/A') . "\n";
        echo "   From: " . ($data['from'] ?? 'N/A') . "\n";
        echo "   Subject: " . ($data['subject'] ?? 'N/A') . "\n";
        if (isset($data['created_at'])) {
            echo "   Created: " . $data['created_at'] . "\n";
        }
    } else {
        echo "   Response: $response\n";
    }
}

// 2. Check Mail Transport
echo "\n2. Checking Mail Transport:\n";
$transport = \Illuminate\Support\Facades\Mail::getSymfonyTransport();
echo "   Transport class: " . get_class($transport) . "\n";

// 3. Test send with detailed tracking
echo "\n3. Sending test email with detailed tracking:\n";

$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(228);
if ($call) {
    app()->instance('current_company_id', $call->company_id);
    
    try {
        // Create the email
        $email = new \App\Mail\CallSummaryEmail(
            $call,
            true,
            false,
            'Deep Debug Test - ' . now()->format('H:i:s'),
            'internal'
        );
        
        // Get the message
        $message = $email->build();
        
        echo "   From: " . config('mail.from.address') . "\n";
        echo "   To: fabianspitzer@icloud.com\n";
        echo "   Subject: " . $email->subject . "\n";
        
        // Send directly
        \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->send($email);
        
        echo "   ✅ Email sent to Resend\n";
        
    } catch (\Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        echo "   Trace: " . $e->getTraceAsString() . "\n";
    }
}

// 4. Check Resend webhook logs
echo "\n4. Checking for Resend webhooks (failed deliveries):\n";
$webhooks = \DB::table('webhook_events')
    ->where('provider', 'resend')
    ->where('created_at', '>', now()->subHours(24))
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

if ($webhooks->count() > 0) {
    foreach ($webhooks as $webhook) {
        $payload = json_decode($webhook->payload, true);
        echo "   - " . $webhook->created_at . ": " . ($payload['type'] ?? 'N/A') . "\n";
        if (isset($payload['data']['email_id'])) {
            echo "     Email ID: " . $payload['data']['email_id'] . "\n";
        }
    }
} else {
    echo "   No recent webhooks found\n";
}

echo "\n=== POSSIBLE ISSUES ===\n";
echo "1. Email bouncing (check Resend dashboard for bounces)\n";
echo "2. Email marked as spam by recipient server\n";
echo "3. Rate limiting\n";
echo "4. Recipient email server blocking\n";