<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;
use App\Models\Call;
use App\Mail\CallSummaryEmail;

echo "=== Test Resend E-Mail Service ===\n\n";

// Check configuration
echo "1. Konfiguration:\n";
echo "   Mail Driver: " . config('mail.default') . "\n";
echo "   Resend API Key: " . (config('services.resend.key') ? '✅ Konfiguriert' : '❌ Fehlt') . "\n";
echo "   From Address: " . config('mail.from.address') . "\n\n";

$timestamp = now()->format('d.m.Y H:i:s');
$testId = uniqid('resend-test-');

// Test 1: Simple text email
echo "2. Sende Test-E-Mail über Resend:\n";
try {
    Mail::raw("Resend Test E-Mail\n\nTest-ID: $testId\nZeit: $timestamp\n\nWenn Sie diese E-Mail erhalten, funktioniert Resend korrekt!", function ($message) use ($testId) {
        $message->to('fabianspitzer@icloud.com')
                ->subject("Resend Test - $testId")
                ->from('info@askproai.de', 'AskProAI via Resend');
    });
    
    echo "   ✅ Test-E-Mail gesendet!\n";
    echo "   Test-ID: $testId\n\n";
    
} catch (\Exception $e) {
    echo "   ❌ Fehler: " . $e->getMessage() . "\n\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n\n";
}

// Test 2: Call Summary via Resend
echo "3. Sende Call Summary über Resend:\n";
try {
    $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(229);
    
    if ($call) {
        Mail::to('fabianspitzer@icloud.com')->send(new CallSummaryEmail(
            $call,
            true,  // include transcript
            false, // no CSV
            "Call Summary via Resend\n\nTest-ID: $testId\nZeit: $timestamp",
            'internal'
        ));
        
        echo "   ✅ Call Summary gesendet!\n";
    } else {
        echo "   ⚠️  Call 229 nicht gefunden\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Fehler: " . $e->getMessage() . "\n";
}

echo "\n=== WICHTIG ===\n";
echo "Die E-Mails sollten SOFORT ankommen, da Resend:\n";
echo "- Optimiert für transaktionale E-Mails ist\n";
echo "- Bereits im SPF-Record erlaubt ist\n";
echo "- Bessere Zustellraten hat\n\n";

echo "Betreff der Test-E-Mail: 'Resend Test - $testId'\n";