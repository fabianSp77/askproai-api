<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Test mit verifiziertem Sender ===\n\n";

$apiKey = config('services.resend.key');

// Test with different from addresses
$testEmails = [
    'onboarding@resend.dev' => 'Resend Default (sollte funktionieren)',
    'info@askproai.de' => 'AskProAI Domain',
];

foreach ($testEmails as $fromEmail => $description) {
    echo "Test mit: $fromEmail ($description)\n";
    
    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'from' => $fromEmail,
        'to' => 'fabianspitzer@icloud.com',
        'subject' => 'Sender Test - ' . $fromEmail . ' - ' . now()->format('H:i:s'),
        'html' => "<h1>Sender Verification Test</h1>
                   <p>From: $fromEmail</p>
                   <p>Description: $description</p>
                   <p>Time: " . now()->format('d.m.Y H:i:s') . "</p>"
    ]));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "   HTTP Code: $httpCode\n";
    echo "   Response: $response\n\n";
    
    if ($httpCode != 200 && $httpCode != 201) {
        $error = json_decode($response, true);
        if (isset($error['message'])) {
            echo "   ❌ ERROR: " . $error['message'] . "\n\n";
        }
    }
}

echo "=== ANALYSE ===\n";
echo "Wenn 'onboarding@resend.dev' funktioniert aber 'info@askproai.de' nicht:\n";
echo "→ Die Domain 'askproai.de' ist nicht in Resend verifiziert!\n\n";

echo "=== LÖSUNG ===\n";
echo "1. Gehen Sie zu: https://resend.com/domains\n";
echo "2. Fügen Sie 'askproai.de' hinzu\n";
echo "3. Fügen Sie die DNS-Records hinzu (SPF, DKIM, etc.)\n";
echo "4. Warten Sie auf Verifizierung\n\n";

echo "=== TEMPORÄRE LÖSUNG ===\n";
echo "Verwenden Sie 'onboarding@resend.dev' als Absender:\n";

// Update .env temporarily
echo "\nÄndere temporär den Absender...\n";
config(['mail.from.address' => 'onboarding@resend.dev']);
config(['mail.from.name' => 'AskProAI (Test)']);

// Send test with verified sender
try {
    $call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(228);
    if ($call) {
        app()->instance('current_company_id', $call->company_id);
        
        \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->send(new \App\Mail\CallSummaryEmail(
            $call,
            true,
            false, // no CSV for now
            'Test mit verifiziertem Sender - ' . now()->format('H:i:s'),
            'internal'
        ));
        
        echo "✅ E-Mail mit verifiziertem Sender gesendet!\n";
        echo "Diese sollte definitiv ankommen.\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}