<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Mail\CallSummaryEmail;
use Illuminate\Support\Facades\Mail;

echo "=== Test E-Mail mit CSV-Anhang ===\n\n";

try {
    $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(229);
    
    if (!$call) {
        echo "❌ Call 229 nicht gefunden!\n";
        exit(1);
    }
    
    echo "Sende E-Mail mit CSV-Anhang an fabianspitzer@icloud.com...\n\n";
    
    // Send with CSV attachment
    Mail::to('fabianspitzer@icloud.com')->send(new CallSummaryEmail(
        $call,
        true,   // include transcript
        true,   // include CSV - WICHTIG: CSV-Anhang aktiviert
        "Diese E-Mail zeigt das aktuelle Design und enthält eine CSV-Datei mit allen Anrufdaten.\n\nBitte prüfen Sie:\n1. Das E-Mail-Design\n2. Die enthaltenen Informationen\n3. Den CSV-Anhang",
        'internal'
    ));
    
    echo "✅ E-Mail mit CSV-Anhang wurde versendet!\n\n";
    
    echo "=== ENTHALTENE INFORMATIONEN ===\n\n";
    
    echo "1. E-MAIL DESIGN:\n";
    echo "   - Professionelles HTML-Template\n";
    echo "   - Responsive Design (Mobile-optimiert)\n";
    echo "   - Farbverlauf im Header (Lila)\n";
    echo "   - Strukturierte Informationsboxen\n";
    echo "   - Action Items mit Icons\n";
    echo "   - Call-to-Action Button\n\n";
    
    echo "2. E-MAIL INHALTE:\n";
    echo "   - Firmenname im Header\n";
    echo "   - Anrufer-Informationen (Name, Telefon)\n";
    echo "   - Datum, Uhrzeit und Dauer\n";
    echo "   - Dringlichkeit (falls vorhanden)\n";
    echo "   - Zusammenfassung des Gesprächs\n";
    echo "   - Erforderliche Maßnahmen\n";
    echo "   - Erfasste Informationen\n";
    echo "   - Transkript (optional)\n";
    echo "   - Kundeninformationen\n\n";
    
    echo "3. CSV-ANHANG ENTHÄLT:\n";
    echo "   - ID, Datum, Uhrzeit\n";
    echo "   - Dauer (Sekunden und formatiert)\n";
    echo "   - Telefonnummer\n";
    echo "   - Kundenname und E-Mail\n";
    echo "   - Filiale\n";
    echo "   - Status und Dringlichkeit\n";
    echo "   - Zusammenfassung\n";
    echo "   - Termin-Informationen\n";
    echo "   - Vollständiges Transkript\n";
    echo "   - Erfasste Daten\n";
    echo "   - Agent Name\n";
    echo "   - Anrufkosten\n";
    echo "   - Zeitstempel\n\n";
    
    echo "4. DATENSCHUTZ-FILTER:\n";
    echo "   Technische Felder werden NICHT angezeigt:\n";
    echo "   - caller_id\n";
    echo "   - twilio_call_sid\n";
    echo "   - direction\n";
    echo "   - Interne IDs\n\n";
    
    echo "5. SICHERHEITSHINWEISE:\n";
    echo "   - Vertraulichkeitshinweis im Footer\n";
    echo "   - Empfängertyp-basierte Inhalte\n";
    echo "   - Keine sensiblen API-Daten\n\n";
    
    // Show what data would be excluded for external recipients
    echo "6. FÜR EXTERNE EMPFÄNGER:\n";
    echo "   Wenn recipientType = 'external':\n";
    echo "   - Zusätzlicher Vertraulichkeitshinweis\n";
    echo "   - Transkript könnte optional ausgeblendet werden\n";
    echo "   - Weniger technische Details\n\n";
    
    echo "Die E-Mail sollte in wenigen Sekunden ankommen.\n";
    
} catch (\Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}