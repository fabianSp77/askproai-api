<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;
use App\Models\Call;
use App\Mail\CallSummaryEmail;

echo "=== Sende Test-E-Mail JETZT ===\n\n";

// Test-Empfänger - NUR fabianspitzer@icloud.com
$recipients = [
    'fabianspitzer@icloud.com'
];

$timestamp = now()->format('d.m.Y H:i:s');

foreach ($recipients as $email) {
    echo "Sende an: $email ... ";
    
    try {
        // 1. Einfache Test-Mail
        Mail::raw("Dies ist eine Test-E-Mail vom AskProAI System.\n\nZeitpunkt: $timestamp\n\nWenn Sie diese E-Mail erhalten, funktioniert das E-Mail-System korrekt.", function ($message) use ($email, $timestamp) {
            $message->to($email)
                    ->subject('AskProAI Test-E-Mail - ' . $timestamp)
                    ->from('info@askproai.de', 'AskProAI System');
        });
        
        echo "✅ Versendet\n";
        
        // 2. Call Summary für Call 229
        $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(229);
        if ($call && $email === 'fabianspitzer@icloud.com') {
            echo "   Sende auch Call Summary ... ";
            
            Mail::to($email)->send(new CallSummaryEmail(
                $call,
                true,  // include transcript
                false, // no CSV
                'Dies ist eine Test-Nachricht. Die E-Mail-Funktion wurde repariert.',
                'internal'
            ));
            
            echo "✅ Call Summary versendet\n";
        }
        
    } catch (\Exception $e) {
        echo "❌ Fehler: " . $e->getMessage() . "\n";
    }
}

echo "\n=== WICHTIG ===\n";
echo "1. E-Mails wurden an folgende Adressen gesendet:\n";
foreach ($recipients as $email) {
    echo "   - $email\n";
}
echo "\n2. Bitte prüfen Sie:\n";
echo "   - Posteingang (kann 1-5 Minuten dauern)\n";
echo "   - SPAM/Junk-Ordner\n";
echo "   - Promotion/Werbung Tab (Gmail)\n";
echo "\n3. Die E-Mail hat folgenden Betreff:\n";
echo "   'AskProAI Test-E-Mail - $timestamp'\n";

// Zusätzliche Diagnose
echo "\n=== SMTP-Status ===\n";
echo "SMTP-Server: " . config('mail.mailers.smtp.host') . "\n";
echo "Port: " . config('mail.mailers.smtp.port') . "\n";
echo "Verschlüsselung: " . config('mail.mailers.smtp.encryption') . "\n";
echo "Benutzername: " . config('mail.mailers.smtp.username') . "\n";
echo "Von-Adresse: " . config('mail.from.address') . "\n";