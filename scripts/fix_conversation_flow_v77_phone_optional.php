<?php
/**
 * Fix Conversation Flow V77 - Make Phone/Email Optional
 *
 * USER REQUIREMENT (2025-11-07):
 * "Telefonnummer ist keine Pflicht, E-Mail ist keine Pflicht"
 * Only NAME is mandatory for bookings.
 *
 * Changes:
 * 1. Update error handler node - only ask for NAME
 * 2. Update global prompt - remove phone/email requirement
 * 3. Version: 76 → 77
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

echo "╔═══════════════════════════════════════════════════════════════╗" . PHP_EOL;
echo "║  🔧 FIX: Make Phone/Email Optional in Conversation Flow     ║" . PHP_EOL;
echo "╚═══════════════════════════════════════════════════════════════╝" . PHP_EOL;
echo PHP_EOL;

$retellApiKey = config('services.retell.api_key');
$flowId = 'conversation_flow_a58405e3f67a';

// Load current flow from backup
$flowPath = '/tmp/conversation_flow_v76_with_v74.json';
if (!file_exists($flowPath)) {
    echo "❌ ERROR: Flow backup not found at {$flowPath}" . PHP_EOL;
    exit(1);
}

$flow = json_decode(file_get_contents($flowPath), true);
echo "✅ Loaded Flow V{$flow['version']}" . PHP_EOL;

// ============================================================================
// FIX #1: Update Global Prompt - Remove Phone/Email Requirement
// ============================================================================
echo PHP_EOL . "═══ FIX #1: Update Global Prompt ═══" . PHP_EOL;

$oldPrompt = $flow['global_prompt'];

$newPrompt = <<<'PROMPT'
# Friseur 1 Voice Assistant V74.1 (Phone/Email Optional)

## ROLLE
Du bist der deutschsprachige Terminassistent von Friseur 1.
Sprich natürlich, freundlich, kurz (max 2 Sätze).

## BEGRÜSSUNG (EXAKT so sagen)
"Willkommen bei Friseur 1! Wenn Sie einen Termin buchen möchten, benötige ich folgende Informationen: Ihren Namen, die gewünschte Dienstleistung sowie Ihr Wunschdatum mit Uhrzeit. Oder haben Sie einen anderen Wunsch?"

## ZEIT-FORMAT (STRIKT)
Uhrzeiten: "15 Uhr 30", "14 Uhr 10", "14 Uhr" (volle Stunde), "9 Uhr 5" (OHNE "null")
Datum: "Freitag, den 23. Dezember" (OHNE Jahr)
NIEMALS: "halb vier", "viertel nach", "2025", "14.5"

## VERFÜGBARKEIT PRÜFEN (KRITISCH)
Bei Anfragen wie "Was ist frei?", "Wann haben Sie Zeit?", "Heute noch was?":

ABLAUF:
1. Sage EINMAL: "Einen Moment, ich schaue nach..."
2. Tool callen (check_availability)
3. SCHWEIGE bis Result da
4. Antworte mit echten Tool-Daten

NIEMALS: Verfügbarkeit raten, Zeiten erfinden, "vermutlich" sagen

## KUNDENDATEN (VOR BUCHUNG)
PFLICHT: Nur Vor- UND Nachname (nicht nur "Max")
OPTIONAL: Telefonnummer, E-Mail (nur wenn Kunde angibt)

Ablauf:
User: "Ja, buchen"
Du: "Gerne! Darf ich noch Ihren vollständigen Namen haben?"
User: "Max Müller"
Du: "Perfekt! Ich buche jetzt..."

NICHT nach Telefon/Email fragen, außer Kunde möchte Bestätigung.

## ALTERNATIVEN (MAX 2)
Wenn Termin nicht frei:
- Nenne MAXIMAL 2 Alternativen
- Wähle die 2 NÄCHSTEN zum Kundenwunsch
- Priorisiere: Gleicher Tag > Gleiche Uhrzeit > Nächster Tag

Beispiel:
User: "Samstag um 10 Uhr?"
Tool: 08:50, 09:45, 10:40, 14:30, 16:00
Du: "Samstag um 10 Uhr leider nicht frei. Wie wäre es mit 10 Uhr 40 oder 9 Uhr 45?"

NICHT: Alle 5 Termine aufzählen

## SERVICES
Mehrdeutig → klären:
- "Haarschnitt" → "Herrenhaarschnitt oder Damenhaarschnitt?"
- "Föhnen" → "Föhnen für Damen oder Herren?"

Synonyme (Backend kennt):
- "Detox" → Hairdetox
- "Herrenschnitt" → Herrenhaarschnitt
- "Strähnchen" → Balayage/Ombré
- "Locken" → Dauerwelle
- "Olaplex" → Rebuild Treatment Olaplex

Preise NUR auf Nachfrage nennen.

## CONTEXT VARIABLES (Auto-Set)
{{customer_name}}, {{customer_phone}}, {{service_name}}, {{appointment_date}}, {{appointment_time}}, {{current_date}}, {{current_time}}, {{day_name}}

NUR nach FEHLENDEN Daten fragen!
Wenn {{service_name}} = "Herrenhaarschnitt" → NICHT nochmal nach Service fragen

## POST-BOOKING
Nach erfolgreicher Buchung:
1. Zusammenfassen: "Ihr Termin für [Service] ist am [Datum] um [Zeit] gebucht."
2. Bestätigung: "Sie erhalten gleich eine Email mit allen Details."
3. Verabschiedung: "Vielen Dank und bis bald!"

## ANTI-REPETITION
NIEMALS wiederholen was bereits gesagt wurde.
"Ich prüfe..." nur EINMAL pro Check.
Bei Tool-Call: WARTEN, NICHTS SAGEN bis Result da.

## VERBOTEN
- Verfügbarkeit ohne Tool raten
- Nach bekannten Daten fragen
- Robotisch wiederholen
- Regionalformen ("halb vier")
- Jahr nennen ("2025")
- Ohne Name buchen
- Nach Telefon/Email fragen (nur wenn Kunde explizit will)
- Lange Monologe
- Technische Begriffe ("API", "System")

---
VERSION: V74.1 (2025-11-07 Phone/Email Optional)
CHANGES: Phone/Email nicht mehr Pflicht, nur Name erforderlich
PROMPT;

$flow['global_prompt'] = $newPrompt;

echo "✅ Global Prompt updated:" . PHP_EOL;
echo "   - Removed: 'Telefonnummer' requirement" . PHP_EOL;
echo "   - Added: Phone/Email nur wenn Kunde angibt" . PHP_EOL;
echo "   - Size: " . strlen($oldPrompt) . " → " . strlen($newPrompt) . " chars" . PHP_EOL;

// ============================================================================
// FIX #2: Update Error Handler Node - Only Ask for Name
// ============================================================================
echo PHP_EOL . "═══ FIX #2: Update Error Handler Node ═══" . PHP_EOL;

$errorNodeIndex = null;
foreach ($flow['nodes'] as $index => $node) {
    if ($node['id'] === 'node_collect_missing_data') {
        $errorNodeIndex = $index;
        break;
    }
}

if ($errorNodeIndex === null) {
    echo "❌ ERROR: node_collect_missing_data not found" . PHP_EOL;
    exit(1);
}

$oldInstruction = $flow['nodes'][$errorNodeIndex]['instruction']['text'];

$flow['nodes'][$errorNodeIndex]['instruction']['text'] =
    'Der vorherige Buchungsversuch ist fehlgeschlagen, weil der Kundenname fehlt. ' .
    'Frage den Kunden nach seinem vollständigen Namen (Vor- UND Nachname). ' .
    'Beispiel: "Ihren vollständigen Namen bitte?" oder "Wie ist Ihr Name?" ' .
    'NICHT nach Telefonnummer oder E-Mail fragen - diese sind optional.';

$flow['nodes'][$errorNodeIndex]['name'] = 'Fehlenden Namen abfragen';

echo "✅ Error handler node updated:" . PHP_EOL;
echo "   - OLD: 'Frage nach Telefonnummer, Name, E-Mail'" . PHP_EOL;
echo "   - NEW: 'Nur nach Name fragen (Pflicht)'" . PHP_EOL;

// ============================================================================
// FIX #3: Update Version
// ============================================================================
echo PHP_EOL . "═══ FIX #3: Update Version ═══" . PHP_EOL;

$flow['version'] = 77;
echo "✅ Version: 76 → 77" . PHP_EOL;

// ============================================================================
// Save Backup
// ============================================================================
$backupPath = '/tmp/conversation_flow_v77_phone_optional.json';
file_put_contents($backupPath, json_encode($flow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo PHP_EOL . "💾 Backup saved: {$backupPath}" . PHP_EOL;

// ============================================================================
// Upload to Retell API
// ============================================================================
echo PHP_EOL . "═══ UPLOAD TO RETELL API ═══" . PHP_EOL;

$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json',
])->patch("https://api.retellai.com/update-conversation-flow/{$flowId}", [
    'global_prompt' => $flow['global_prompt'],
    'nodes' => $flow['nodes'],
    'start_node_id' => $flow['start_node_id'],
    'start_speaker' => $flow['start_speaker'],
    'begin_after_user_silence_ms' => $flow['begin_after_user_silence_ms'],
    'tools' => $flow['tools'],
]);

if ($response->successful()) {
    echo "✅ SUCCESS: Conversation Flow V77 uploaded!" . PHP_EOL;
    echo PHP_EOL;
    echo "╔═══════════════════════════════════════════════════════════════╗" . PHP_EOL;
    echo "║  ✅ DEPLOYMENT COMPLETE - V77                                ║" . PHP_EOL;
    echo "╚═══════════════════════════════════════════════════════════════╝" . PHP_EOL;
    echo PHP_EOL;
    echo "Changes:" . PHP_EOL;
    echo "1. ✅ Global Prompt: Phone/Email nicht mehr Pflicht" . PHP_EOL;
    echo "2. ✅ Error Handler: Nur nach Name fragen" . PHP_EOL;
    echo "3. ✅ Backend: Phone/Email fallback implemented" . PHP_EOL;
    echo PHP_EOL;
    echo "🧪 Ready for testing with:" . PHP_EOL;
    echo "   - Booking WITHOUT phone (should use fallback)" . PHP_EOL;
    echo "   - Error should only ask for NAME" . PHP_EOL;
    echo "   - Phone/Email optional in conversation" . PHP_EOL;
    exit(0);
} else {
    echo "❌ FAILED: " . $response->status() . PHP_EOL;
    echo $response->body() . PHP_EOL;
    exit(1);
}
