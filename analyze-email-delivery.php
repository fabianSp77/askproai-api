<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;

echo "=== E-Mail Delivery Analyse ===\n\n";

// 1. Test SMTP Connection
echo "1. SMTP Verbindungstest:\n";
try {
    $transport = Mail::getSymfonyTransport();
    $transport->start();
    echo "   ✅ SMTP-Verbindung erfolgreich\n";
    echo "   Server: " . config('mail.mailers.smtp.host') . ":" . config('mail.mailers.smtp.port') . "\n";
    echo "   User: " . config('mail.mailers.smtp.username') . "\n\n";
} catch (\Exception $e) {
    echo "   ❌ SMTP-Verbindung fehlgeschlagen: " . $e->getMessage() . "\n\n";
}

// 2. Check Mail Logs
echo "2. Letzte Mail-Log Einträge:\n";
$logFile = storage_path('logs/laravel.log');
$lines = [];
if (file_exists($logFile)) {
    $handle = fopen($logFile, 'r');
    if ($handle) {
        while (!feof($handle)) {
            $line = fgets($handle);
            if (stripos($line, 'mail') !== false || stripos($line, 'smtp') !== false) {
                $lines[] = trim($line);
            }
        }
        fclose($handle);
    }
}

// Show last 5 mail-related logs
$mailLogs = array_slice($lines, -5);
foreach ($mailLogs as $log) {
    echo "   " . substr($log, 0, 150) . "...\n";
}

// 3. Test with different recipient
echo "\n3. Test mit verschiedenen E-Mail-Anbietern:\n";
$testRecipients = [
    'fabianspitzer@icloud.com' => 'iCloud',
    'test@askproai.de' => 'AskProAI (intern)',
];

foreach ($testRecipients as $email => $provider) {
    echo "   Testing $provider ($email)... ";
    try {
        $testSubject = 'Delivery Test - ' . now()->format('H:i:s');
        Mail::raw("Test-Nachricht für $provider\nZeit: " . now()->format('d.m.Y H:i:s'), function ($message) use ($email, $testSubject) {
            $message->to($email)
                    ->subject($testSubject)
                    ->from('info@askproai.de', 'AskProAI System')
                    ->priority(1); // High priority
        });
        echo "✅ Gesendet\n";
    } catch (\Exception $e) {
        echo "❌ Fehler: " . $e->getMessage() . "\n";
    }
}

// 4. Check DNS/SPF Records
echo "\n4. E-Mail-Authentifizierung prüfen:\n";
$domain = 'askproai.de';

// Check SPF
$txtRecords = dns_get_record($domain, DNS_TXT);
$spfFound = false;
foreach ($txtRecords as $record) {
    if (isset($record['txt']) && strpos($record['txt'], 'v=spf1') === 0) {
        echo "   SPF Record: " . $record['txt'] . "\n";
        $spfFound = true;
    }
}
if (!$spfFound) {
    echo "   ⚠️  Kein SPF-Record gefunden!\n";
}

// Check MX
$mxRecords = [];
if (getmxrr($domain, $mxRecords)) {
    echo "   MX Records: " . implode(', ', $mxRecords) . "\n";
}

// 5. Mail Queue/Failed Jobs detailed check
echo "\n5. Detaillierte Queue-Prüfung:\n";
$jobs = \DB::table('jobs')->get();
$failedJobs = \DB::table('failed_jobs')->orderBy('id', 'desc')->limit(10)->get();

echo "   Active Jobs: " . $jobs->count() . "\n";
echo "   Failed Jobs (total): " . \DB::table('failed_jobs')->count() . "\n";

if ($failedJobs->count() > 0) {
    echo "\n   Letzte fehlgeschlagene Jobs:\n";
    foreach ($failedJobs as $job) {
        $payload = json_decode($job->payload, true);
        if (isset($payload['displayName']) && strpos($payload['displayName'], 'Mail') !== false) {
            echo "   - " . $job->failed_at . ": " . $payload['displayName'] . "\n";
            $exception = json_decode($job->exception, true);
            if ($exception && isset($exception['message'])) {
                echo "     Error: " . substr($exception['message'], 0, 100) . "...\n";
            }
        }
    }
}

// 6. Alternative Mail Test
echo "\n6. Alternative Mail-Test (mit SwiftMailer direkt):\n";
try {
    $message = new \Symfony\Component\Mime\Email();
    $message->from(new \Symfony\Component\Mime\Address('info@askproai.de', 'AskProAI Direct Test'))
            ->to('fabianspitzer@icloud.com')
            ->subject('Direct SMTP Test - ' . now()->format('H:i:s'))
            ->text('Dieser Test umgeht Laravel Mail und nutzt Symfony Mailer direkt.')
            ->priority(\Symfony\Component\Mime\Email::PRIORITY_HIGH);
    
    $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
        'smtp.udag.de',
        465,
        true // TLS
    );
    $transport->setUsername('askproai-de-0004');
    $transport->setPassword('Qwe421as1!1');
    
    $mailer = new \Symfony\Component\Mailer\Mailer($transport);
    $mailer->send($message);
    
    echo "   ✅ Direct SMTP Test erfolgreich\n";
} catch (\Exception $e) {
    echo "   ❌ Direct SMTP Test fehlgeschlagen: " . $e->getMessage() . "\n";
}

echo "\n=== EMPFEHLUNGEN ===\n";
echo "1. Prüfen Sie den E-Mail-Server Log bei UDAG\n";
echo "2. Kontaktieren Sie UDAG Support bezüglich Delivery-Problemen\n";
echo "3. Überprüfen Sie, ob die IP des Servers auf einer Blacklist steht\n";
echo "4. Erwägen Sie einen professionellen E-Mail-Service (SendGrid, Mailgun)\n";