<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;

echo "=== E-Mail Delivery Debugging ===\n\n";

// 1. Test verschiedene E-Mail-Adressen
$testCases = [
    ['email' => 'test@askproai.de', 'expected' => 'sollte funktionieren (interne Domain)'],
    ['email' => 'info@askproai.de', 'expected' => 'sollte funktionieren (interne Domain)'],
    ['email' => 'stephan@boehm-software.de', 'expected' => 'externe Domain - könnte blockiert sein'],
    ['email' => 'fabianspitzer@icloud.com', 'expected' => 'externe Domain - könnte blockiert sein'],
    ['email' => 'test@gmail.com', 'expected' => 'große Provider - Test'],
    ['email' => 'test@gmx.de', 'expected' => 'deutscher Provider - Test'],
];

echo "1. Teste verschiedene E-Mail-Domänen:\n";
echo str_repeat('-', 80) . "\n";

$workingEmails = [];
$failedEmails = [];

foreach ($testCases as $test) {
    echo "Teste: {$test['email']} ({$test['expected']})\n";
    
    try {
        // Verwende raw() für direkten Test ohne Queue
        Mail::raw('Test-E-Mail vom AskProAI System. Zeit: ' . now(), function ($message) use ($test) {
            $message->to($test['email'])
                    ->subject('AskProAI Test - ' . now()->format('H:i:s'));
        });
        
        echo "  ✅ Erfolgreich versendet\n";
        $workingEmails[] = $test['email'];
        
    } catch (\Exception $e) {
        echo "  ❌ Fehler: " . $e->getMessage() . "\n";
        $failedEmails[] = ['email' => $test['email'], 'error' => $e->getMessage()];
    }
    
    echo "\n";
}

// 2. Mail Transport Details
echo "\n2. Mail Transport Configuration:\n";
echo str_repeat('-', 80) . "\n";

$transport = Mail::getSwiftMailer()->getTransport();
echo "Transport Type: " . get_class($transport) . "\n";

if (method_exists($transport, 'getHost')) {
    echo "Host: " . $transport->getHost() . "\n";
    echo "Port: " . $transport->getPort() . "\n";
    echo "Encryption: " . $transport->getEncryption() . "\n";
    echo "Username: " . $transport->getUsername() . "\n";
}

// 3. DNS/MX Record Check
echo "\n3. DNS/MX Record Checks:\n";
echo str_repeat('-', 80) . "\n";

$domains = ['askproai.de', 'boehm-software.de', 'icloud.com'];
foreach ($domains as $domain) {
    echo "Domain: $domain\n";
    
    // Check MX records
    $mxRecords = [];
    if (getmxrr($domain, $mxRecords)) {
        echo "  MX Records: " . implode(', ', array_slice($mxRecords, 0, 3)) . "\n";
    } else {
        echo "  MX Records: KEINE GEFUNDEN!\n";
    }
    
    // Check A record
    $ip = gethostbyname($domain);
    if ($ip !== $domain) {
        echo "  A Record: $ip\n";
    } else {
        echo "  A Record: NICHT AUFLÖSBAR!\n";
    }
    echo "\n";
}

// 4. Test mit verschiedenen From-Adressen
echo "\n4. Test mit verschiedenen Absender-Adressen:\n";
echo str_repeat('-', 80) . "\n";

$fromAddresses = [
    'info@askproai.de',
    'noreply@askproai.de',
    'system@askproai.de'
];

foreach ($fromAddresses as $from) {
    echo "Teste mit Absender: $from\n";
    try {
        Mail::raw('Test mit Absender: ' . $from, function ($message) use ($from) {
            $message->to('test@askproai.de')
                    ->from($from, 'AskProAI System')
                    ->subject('Absender-Test - ' . now()->format('H:i:s'));
        });
        echo "  ✅ Erfolgreich\n";
    } catch (\Exception $e) {
        echo "  ❌ Fehler: " . substr($e->getMessage(), 0, 100) . "...\n";
    }
}

// 5. Zusammenfassung
echo "\n\n=== ZUSAMMENFASSUNG ===\n";
echo "Funktionierende E-Mails: " . count($workingEmails) . "\n";
if (count($workingEmails) > 0) {
    echo "  - " . implode("\n  - ", $workingEmails) . "\n";
}

echo "\nFehlgeschlagene E-Mails: " . count($failedEmails) . "\n";
if (count($failedEmails) > 0) {
    foreach ($failedEmails as $failed) {
        echo "  - {$failed['email']}: " . substr($failed['error'], 0, 60) . "...\n";
    }
}

// 6. Mögliche Lösungen
echo "\n=== MÖGLICHE URSACHEN ===\n";
echo "1. SMTP-Server Whitelist: Der Server akzeptiert nur bestimmte Empfänger-Domains\n";
echo "2. Relay-Beschränkungen: Der Server erlaubt kein Relay zu externen Domains\n";
echo "3. SPF/DKIM fehlt: E-Mails werden vom Empfänger-Server abgelehnt\n";
echo "4. Rate Limiting: Zu viele E-Mails in kurzer Zeit\n";
echo "5. Firewall/Port-Blockierung: Ausgehende Verbindungen blockiert\n";

echo "\n=== NÄCHSTE SCHRITTE ===\n";
echo "1. Kontaktieren Sie UD·AG Support und fragen Sie nach:\n";
echo "   - Relay-Berechtigungen für externe Domains\n";
echo "   - Whitelist-Einstellungen\n";
echo "   - SPF/DKIM Konfiguration\n";
echo "2. Prüfen Sie die E-Mail-Logs beim Provider\n";
echo "3. Testen Sie alternative SMTP-Services (SendGrid, Mailgun, etc.)\n";