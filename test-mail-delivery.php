<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;
use App\Mail\CallSummaryEmail;
use App\Models\Call;

echo "=== E-Mail Zustellungstest ===\n\n";

// 1. Konfiguration prüfen
echo "1. Mail-Konfiguration:\n";
echo "   Driver: " . config('mail.default') . "\n";
echo "   Host: " . config('mail.mailers.smtp.host') . "\n";
echo "   Port: " . config('mail.mailers.smtp.port') . "\n";
echo "   Encryption: " . config('mail.mailers.smtp.encryption') . "\n";
echo "   Username: " . config('mail.mailers.smtp.username') . "\n";
echo "   From: " . config('mail.from.address') . "\n\n";

// 2. Test mit verschiedenen E-Mail-Adressen
$testEmails = [
    'test@askproai.de',
    'info@askproai.de',
    'stephan@askproai.de',
    'fabian@askproai.de'
];

echo "2. Teste E-Mail-Versand an verschiedene Adressen:\n";

foreach ($testEmails as $email) {
    echo "   Teste: {$email} ... ";
    
    try {
        // Direkter Versand (nicht über Queue)
        Mail::send([], [], function ($message) use ($email) {
            $message->to($email)
                    ->subject('Test E-Mail - ' . now()->format('d.m.Y H:i:s'))
                    ->html('<p>Dies ist eine Test-E-Mail vom AskProAI System.</p><p>Zeitstempel: ' . now() . '</p>');
        });
        
        echo "✅ Erfolgreich\n";
        break; // Stoppe nach erster erfolgreicher E-Mail
        
    } catch (\Exception $e) {
        echo "❌ Fehler: " . $e->getMessage() . "\n";
        
        // Detaillierte Fehleranalyse
        if (strpos($e->getMessage(), 'Domain not found') !== false) {
            echo "      → Domain existiert nicht oder ist nicht erreichbar\n";
        } elseif (strpos($e->getMessage(), 'Recipient address rejected') !== false) {
            echo "      → Empfänger-Adresse wurde vom Server abgelehnt\n";
        } elseif (strpos($e->getMessage(), 'Failed to authenticate') !== false) {
            echo "      → SMTP-Authentifizierung fehlgeschlagen\n";
        }
    }
}

// 3. Queue-Status prüfen
echo "\n3. Queue-Status:\n";
$jobs = \DB::table('jobs')->count();
$failedJobs = \DB::table('failed_jobs')->count();
echo "   Jobs in Queue: {$jobs}\n";
echo "   Failed Jobs: {$failedJobs}\n";

// 4. Horizon Status
echo "\n4. Horizon Status:\n";
$horizonStatus = shell_exec('php artisan horizon:status 2>&1');
echo "   " . trim($horizonStatus) . "\n";

// 5. Test mit Call Summary Email
echo "\n5. Teste Call Summary Email:\n";
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(229);
if ($call) {
    try {
        $testEmail = 'test@askproai.de';
        echo "   Sende Call Summary an {$testEmail}...\n";
        
        // Direkt senden (nicht über Queue)
        Mail::to($testEmail)->send(new CallSummaryEmail(
            $call,
            true,  // include transcript
            false, // include CSV
            'Test-Nachricht',
            'internal'
        ));
        
        echo "   ✅ Call Summary erfolgreich gesendet\n";
    } catch (\Exception $e) {
        echo "   ❌ Fehler: " . $e->getMessage() . "\n";
    }
}

echo "\n=== EMPFEHLUNGEN ===\n";
echo "1. Prüfen Sie, ob die Domain 'askproai.de' korrekt konfiguriert ist\n";
echo "2. Stellen Sie sicher, dass SPF/DKIM Records gesetzt sind\n";
echo "3. Überprüfen Sie die Firewall-Einstellungen (Port 465 muss offen sein)\n";
echo "4. Kontaktieren Sie UD·AG Support für Mail-Server-Status\n";