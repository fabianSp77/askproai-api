<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST E-Mail mit und ohne CSV ===\n\n";

$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(228);
if (!$call) {
    echo "❌ Call 228 not found!\n";
    exit(1);
}

app()->instance('current_company_id', $call->company_id);

// Test 1: Without CSV
echo "1. Test OHNE CSV-Anhang:\n";
try {
    $mail = new \App\Mail\CallSummaryEmail(
        $call,
        true,  // includeSummary
        false, // NO CSV
        'Test OHNE CSV - ' . now()->format('H:i:s'),
        'internal'
    );
    
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->send($mail);
    echo "   ✅ E-Mail ohne CSV gesendet\n\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
}

// Wait a bit
sleep(2);

// Test 2: With CSV
echo "2. Test MIT CSV-Anhang:\n";
try {
    $mail = new \App\Mail\CallSummaryEmail(
        $call,
        true,  // includeSummary
        true,  // WITH CSV
        'Test MIT CSV - ' . now()->format('H:i:s'),
        'internal'
    );
    
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->send($mail);
    echo "   ✅ E-Mail mit CSV gesendet\n\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n\n";
}

// Test 3: Different recipient
echo "3. Test mit anderer E-Mail-Adresse (Gmail):\n";
$testEmails = [
    'test@gmail.com' => 'Gmail Test',
    'test@outlook.com' => 'Outlook Test',
];

foreach ($testEmails as $email => $label) {
    echo "   Hinweis: Ersetzen Sie '$email' mit einer echten E-Mail-Adresse für $label\n";
}

echo "\n=== ANALYSE ===\n";
echo "Wenn die E-Mail OHNE CSV ankommt, aber MIT CSV nicht:\n";
echo "→ Problem mit CSV-Generierung oder Attachment-Größe\n\n";

echo "Wenn BEIDE nicht ankommen:\n";
echo "→ Problem mit iCloud Mail oder Spam-Filter\n\n";

echo "Prüfen Sie:\n";
echo "1. Spam-Ordner bei iCloud\n";
echo "2. Resend Dashboard für Bounce-Nachrichten\n";
echo "3. Testen Sie mit einer Gmail-Adresse\n";