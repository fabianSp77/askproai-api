<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;

echo "=== E-Mail Header Test ===\n\n";

$timestamp = now()->format('d.m.Y H:i:s');
$testId = uniqid('test-');

// Send test email with detailed headers
try {
    Mail::send([], [], function ($message) use ($timestamp, $testId) {
        $message->to('fabianspitzer@icloud.com')
                ->subject("AskProAI Header Test - $testId")
                ->from('info@askproai.de', 'AskProAI System')
                ->replyTo('info@askproai.de', 'AskProAI Support')
                ->priority(1)
                ->getHeaders()
                ->addTextHeader('X-Test-ID', $testId)
                ->addTextHeader('X-Sent-Time', $timestamp);
        
        // Set plain text body
        $message->text("E-Mail Header Test\n\nTest ID: $testId\nZeit: $timestamp\n\nWichtig: Diese E-Mail testet die Header-Konfiguration.\n\nBitte antworten Sie auf diese E-Mail mit dem Betreff 'ERHALTEN' wenn Sie diese Nachricht sehen.");
    });
    
    echo "✅ Test-E-Mail gesendet\n";
    echo "   Test-ID: $testId\n";
    echo "   Zeit: $timestamp\n";
    echo "   An: fabianspitzer@icloud.com\n\n";
    
} catch (\Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n\n";
}

// Check server configuration
echo "=== Server-Konfiguration ===\n";
echo "1. Hostname: " . gethostname() . "\n";
echo "2. Server IP: " . gethostbyname(gethostname()) . "\n";
echo "3. PHP Mail Settings:\n";
echo "   sendmail_path: " . ini_get('sendmail_path') . "\n";
echo "   SMTP: " . ini_get('SMTP') . "\n";
echo "   smtp_port: " . ini_get('smtp_port') . "\n\n";

// Check SPF alignment
echo "=== SPF Analyse ===\n";
echo "SPF Record: v=spf1 include:spf.resend.com -all\n";
echo "⚠️  PROBLEM: SPF erlaubt nur 'spf.resend.com' aber wir senden über 'smtp.udag.de'\n";
echo "Dies könnte dazu führen, dass E-Mails als Spam markiert oder abgelehnt werden!\n\n";

echo "=== LÖSUNGSVORSCHLÄGE ===\n";
echo "1. SPF-Record anpassen:\n";
echo "   v=spf1 include:spf.udag.de include:spf.resend.com -all\n\n";
echo "2. Oder: Resend.com als E-Mail-Service nutzen (da SPF bereits konfiguriert)\n\n";
echo "3. DKIM-Signatur hinzufügen für bessere Authentifizierung\n\n";

// Test with internal domain
echo "=== Test mit interner Domain ===\n";
try {
    Mail::raw("Interner Test - $timestamp", function ($message) use ($timestamp) {
        $message->to('test@askproai.de')
                ->subject("Interner Test - $timestamp")
                ->from('info@askproai.de', 'AskProAI System');
    });
    echo "✅ Interne E-Mail gesendet an test@askproai.de\n";
} catch (\Exception $e) {
    echo "❌ Fehler bei interner E-Mail: " . $e->getMessage() . "\n";
}