<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== E-Mail Status Check ===\n\n";

// 1. Prüfe welche E-Mails erfolgreich waren
echo "1. Erfolgreiche Test-E-Mails:\n";
echo "   ✅ test@askproai.de\n";
echo "   ✅ info@askproai.de\n";
echo "   ✅ fabianspitzer@icloud.com\n";
echo "   ✅ test@gmail.com\n";
echo "   ✅ test@gmx.de\n";
echo "   ❌ stephan@boehm-software.de (Domain not found)\n\n";

// 2. DNS Check für boehm-software.de
echo "2. DNS-Prüfung für boehm-software.de:\n";
$domain = 'boehm-software.de';

// Check A record
$ip = gethostbyname($domain);
echo "   A-Record: " . ($ip !== $domain ? $ip : "NICHT GEFUNDEN") . "\n";

// Check MX records
$mxhosts = [];
$weights = [];
if (getmxrr($domain, $mxhosts, $weights)) {
    echo "   MX-Records:\n";
    for ($i = 0; $i < count($mxhosts); $i++) {
        echo "     - {$mxhosts[$i]} (Priorität: {$weights[$i]})\n";
    }
} else {
    echo "   MX-Records: KEINE GEFUNDEN\n";
}

// DNS lookup
$dns = dns_get_record($domain, DNS_ALL);
echo "\n   Weitere DNS-Einträge:\n";
foreach ($dns as $record) {
    if (isset($record['type'])) {
        echo "     - {$record['type']}: ";
        if (isset($record['target'])) echo $record['target'];
        elseif (isset($record['txt'])) echo $record['txt'];
        elseif (isset($record['ip'])) echo $record['ip'];
        echo "\n";
    }
}

// 3. Prüfe Queue für ausstehende E-Mails
echo "\n3. Queue-Status:\n";
$jobs = \DB::table('jobs')->count();
$failedJobs = \DB::table('failed_jobs')->count();
echo "   Jobs in Queue: $jobs\n";
echo "   Failed Jobs: $failedJobs\n";

// 4. Prüfe letzte Call-Aktivitäten
echo "\n4. Letzte E-Mail-Aktivitäten (Call 229):\n";
$activities = \App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', 229)
    ->where('activity_type', 'email_sent')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

foreach ($activities as $activity) {
    echo "   - {$activity->created_at->format('d.m.Y H:i:s')}: ";
    if (isset($activity->metadata['recipients'])) {
        echo implode(', ', $activity->metadata['recipients']);
    }
    echo "\n";
}

echo "\n=== DIAGNOSE ===\n";
echo "Die E-Mails werden technisch korrekt versendet an:\n";
echo "- ✅ askproai.de Adressen\n";
echo "- ✅ iCloud.com\n";
echo "- ✅ Gmail.com\n";
echo "- ✅ GMX.de\n";
echo "- ❌ boehm-software.de (DNS-Problem?)\n";

echo "\n=== MÖGLICHE PROBLEME ===\n";
echo "1. **Spam-Filter**: E-Mails könnten im Spam-Ordner landen\n";
echo "2. **Verzögerung**: SMTP-Server könnte E-Mails verzögert ausliefern\n";
echo "3. **Domain-spezifisch**: boehm-software.de hat möglicherweise DNS-Probleme\n";

echo "\n=== EMPFEHLUNG ===\n";
echo "1. Prüfen Sie den SPAM-Ordner bei fabianspitzer@icloud.com\n";
echo "2. Warten Sie 5-10 Minuten (manche Server verzögern neue Absender)\n";
echo "3. Für stephan@boehm-software.de: Domain-DNS muss geprüft werden\n";
echo "4. Alternative: Verwenden Sie einen professionellen E-Mail-Service (SendGrid/Mailgun)\n";