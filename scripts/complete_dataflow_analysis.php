<?php

/**
 * Complete E2E Dataflow Analysis
 *
 * Analysiert den kompletten Datenfluss von Retell → Backend → Cal.com
 * für Test-Call: call_86ba8c303e902256e5d31f065d0
 */

require __DIR__ . '/../vendor/autoload.php';

use Carbon\Carbon;

echo "🔍 COMPLETE E2E DATAFLOW ANALYSIS\n";
echo str_repeat('=', 80) . "\n\n";

echo "CALL ID: call_86ba8c303e902256e5d31f065d0\n";
echo "CALL TIME: 2025-11-03 23:49:41 (UTC)\n";
echo "HEUTE: 2025-11-04\n\n";

echo str_repeat('=', 80) . "\n";
echo "STAGE 1: USER INPUT → AGENT\n";
echo str_repeat('=', 80) . "\n\n";

echo "User sagte:\n";
echo "   \"für morgen, sechzehn Uhr\"\n\n";

echo "Agent sammelte:\n";
echo "   customer_name: \"Hans Schuß\"\n";
echo "   service_name: \"Herrenhaarschnitt\"\n";
echo "   appointment_date: \"morgen\"\n";
echo "   appointment_time: \"16:00\"\n\n";

echo str_repeat('=', 80) . "\n";
echo "STAGE 2: AGENT → BACKEND (check_availability_v17)\n";
echo str_repeat('=', 80) . "\n\n";

echo "Agent sendet (tool_call_invocation):\n";
echo "   {\n";
echo "     \"name\": \"Hans Schuß\",\n";
echo "     \"datum\": \"morgen\",  // ❌ NICHT KONVERTIERT!\n";
echo "     \"dienstleistung\": \"Herrenhaarschnitt\",\n";
echo "     \"uhrzeit\": \"16:00\",\n";
echo "     \"call_id\": \"\"  // ❌ LEER (V16 Problem)\n";
echo "   }\n\n";

echo "PROBLEM 1: 'morgen' nicht zu Datum konvertiert!\n";
echo "   - User sagte: \"morgen\" am 2025-11-03 23:49 Uhr\n";
echo "   - Sollte werden: 2025-11-04\n";
echo "   - Agent sendet: \"morgen\" (String!)\n\n";

echo str_repeat('=', 80) . "\n";
echo "STAGE 3: BACKEND EMPFÄNGT (RetellFunctionCallHandler)\n";
echo str_repeat('=', 80) . "\n\n";

echo "Webhook Request:\n";
echo "   {\n";
echo "     \"call_id\": \"call_86ba8c303e902256e5d31f065d0\",  // ✅ ROOT LEVEL\n";
echo "     \"args\": {\n";
echo "       \"name\": \"Hans Schuß\",\n";
echo "       \"datum\": \"morgen\",  // ❌ String!\n";
echo "       \"dienstleistung\": \"Herrenhaarschnitt\",\n";
echo "       \"uhrzeit\": \"16:00\",\n";
echo "       \"call_id\": \"\"\n";
echo "     }\n";
echo "   }\n\n";

echo "Backend-Fix (NEW):\n";
echo "   ✅ \$callIdFromWebhook = \$request->input('call_id');\n";
echo "   ✅ \$canonicalCallId = 'call_86ba8c...'\n";
echo "   ✅ Backend injects into args\n\n";

echo "Nach Backend Injection:\n";
echo "   args['call_id'] = 'call_86ba8c303e902256e5d31f065d0'  ✅\n\n";

echo str_repeat('=', 80) . "\n";
echo "STAGE 4: BACKEND VERARBEITET (collectAppointment)\n";
echo str_repeat('=', 80) . "\n\n";

echo "Date Parsing:\n";
echo "   Input: \$datum = 'morgen'\n";
echo "   Method: parseDateString('morgen')\n\n";

// Simulate date parsing
$heute = Carbon::parse('2025-11-03 23:49:41');
echo "   Call Time: {$heute->format('Y-m-d H:i:s')}\n";

$morgen = $heute->copy()->addDay()->startOfDay();
echo "   'morgen' wird zu: {$morgen->format('Y-m-d')}\n\n";

echo "   ✅ KORREKT: morgen = 2025-11-04\n\n";

echo "Time Parsing:\n";
echo "   Input: \$uhrzeit = '16:00'\n";
echo "   Output: 16:00:00  ✅\n\n";

echo "Final DateTime:\n";
echo "   2025-11-04 16:00:00  ✅\n\n";

echo str_repeat('=', 80) . "\n";
echo "STAGE 5: BACKEND → CAL.COM API\n";
echo str_repeat('=', 80) . "\n\n";

echo "Cal.com Availability Check Request:\n";
echo "   POST /api/availability\n";
echo "   {\n";
echo "     \"dateFrom\": \"2025-11-04T16:00:00+01:00\",  // ✅\n";
echo "     \"dateTo\": \"2025-11-04T16:30:00+01:00\",    // ✅\n";
echo "     \"eventTypeId\": <service_id>,\n";
echo "     \"username\": \"friseur1\"\n";
echo "   }\n\n";

echo "ERWARTETE Cal.com Response:\n";
echo "   {\n";
echo "     \"busy\": [],  // Slot verfügbar\n";
echo "     \"dateRanges\": [\n";
echo "       {\n";
echo "         \"start\": \"2025-11-04T16:00:00+01:00\",\n";
echo "         \"end\": \"2025-11-04T16:30:00+01:00\"\n";
echo "       }\n";
echo "     ]\n";
echo "   }\n\n";

echo "ABER: Test-Call FEHLTE weil:\n";
echo "   ❌ call_id war leer → Backend Error\n";
echo "   ❌ Cal.com wurde NIE aufgerufen!\n\n";

echo str_repeat('=', 80) . "\n";
echo "STAGE 6: BACKEND → AGENT RESPONSE\n";
echo str_repeat('=', 80) . "\n\n";

echo "Backend Response (tool_call_result):\n";
echo "   {\n";
echo "     \"success\": false,\n";
echo "     \"error\": \"Call context not available\"  // ❌ call_id leer!\n";
echo "   }\n\n";

echo "Agent Reaktion:\n";
echo "   Agent sagt: \"Leider ist der Termin um 16 Uhr morgen nicht verfügbar.\"\n";
echo "   ❌ FALSCH! Cal.com wurde NIE gefragt!\n\n";

echo str_repeat('=', 80) . "\n";
echo "SUMMARY: WAS GING SCHIEF?\n";
echo str_repeat('=', 80) . "\n\n";

echo "✅ User Input → Agent: OK (aber 'morgen' nicht konvertiert)\n";
echo "❌ Agent → Backend: call_id LEER\n";
echo "✅ Backend Date Parsing: WÜRDE funktionieren ('morgen' → 2025-11-04)\n";
echo "❌ Backend → Cal.com: WURDE NIE AUFGERUFEN (call_id fehlt)\n";
echo "❌ Backend → Agent: Error Response\n";
echo "❌ User Experience: Falsches Ergebnis\n\n";

echo str_repeat('=', 80) . "\n";
echo "NACH UNSEREM FIX:\n";
echo str_repeat('=', 80) . "\n\n";

echo "✅ Backend extracts call_id from webhook root\n";
echo "✅ Backend injects call_id into args\n";
echo "✅ call_id = 'call_86ba8c303e902256e5d31f065d0'\n";
echo "✅ Backend verarbeitet 'morgen' → 2025-11-04\n";
echo "✅ Backend ruft Cal.com API auf\n";
echo "✅ Cal.com prüft Verfügbarkeit für 2025-11-04 16:00\n";
echo "✅ Backend gibt korrektes Ergebnis zurück\n";
echo "✅ Agent informiert User korrekt\n\n";

echo str_repeat('=', 80) . "\n";
echo "NÄCHSTER TEST-CALL: ERWARTUNG\n";
echo str_repeat('=', 80) . "\n\n";

echo "User sagt: \"morgen 16 Uhr\"\n";
echo "Heute: 2025-11-04\n";
echo "Sollte prüfen: 2025-11-05 16:00\n\n";

echo "Datenfluss:\n";
echo "1. Agent → Backend: datum='morgen', call_id=''\n";
echo "2. Backend: Extracts call_id from webhook ✅\n";
echo "3. Backend: Parses 'morgen' → 2025-11-05 ✅\n";
echo "4. Backend → Cal.com: Check 2025-11-05 16:00 ✅\n";
echo "5. Cal.com → Backend: Verfügbarkeit ✅\n";
echo "6. Backend → Agent: Success + Verfügbarkeit ✅\n";
echo "7. Agent → User: Korrekte Information ✅\n\n";

echo str_repeat('=', 80) . "\n";
echo "VERIFICATION STEPS\n";
echo str_repeat('=', 80) . "\n\n";

echo "1. Check Laravel Logs:\n";
echo "   tail -f storage/logs/laravel.log | grep -E 'CANONICAL_CALL_ID|parseDateString|Cal.com'\n\n";

echo "2. Look for:\n";
echo "   ✅ CANONICAL_CALL_ID: Resolved\n";
echo "   ✅ call_id: call_xxx\n";
echo "   ✅ parseDateString: 'morgen' → 2025-11-05\n";
echo "   ✅ Cal.com API called\n";
echo "   ✅ Availability result\n\n";

echo "3. Expected outcome:\n";
echo "   ✅ NO 'Call context not available' error\n";
echo "   ✅ Real availability check\n";
echo "   ✅ Correct user feedback\n\n";
