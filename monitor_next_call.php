<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Call;

echo "\n";
echo "════════════════════════════════════════════════════════════════════\n";
echo "  🔴 LIVE MONITORING - PHONE NUMBER FIX AKTIV                      \n";
echo "════════════════════════════════════════════════════════════════════\n";
echo "Zeit: " . date('H:i:s') . "\n\n";

echo "✅ FIXES IMPLEMENTIERT:\n";
echo "   1. Phone Number Lookup in collect-appointment hinzugefügt\n";
echo "   2. to_number wird jetzt gespeichert\n";
echo "   3. Company ID wird aus Phone Number ermittelt\n\n";

$lastCallId = Call::max('id') ?: 0;
echo "📊 Baseline: Letzte Call ID = $lastCallId\n\n";

echo "📱 BITTE RUFEN SIE JETZT AN: +493083793369\n";
echo "════════════════════════════════════════════════════════════════════\n\n";

// Monitor for 120 seconds
for ($i = 0; $i < 120; $i++) {
    $newCall = Call::where('id', '>', $lastCallId)->first();

    if ($newCall) {
        echo "\n🔔 NEUER ANRUF ERKANNT!\n";
        echo "════════════════════════════════════════════════════════════════════\n\n";

        echo "CALL DETAILS:\n";
        echo "  Call ID:        {$newCall->id}\n";
        echo "  Retell ID:      {$newCall->retell_call_id}\n";
        echo "  To Number:      {$newCall->to_number}\n";
        echo "  Status:         {$newCall->status}\n\n";

        echo "KRITISCHE PRÜFUNG:\n";

        // Phone Number Check
        if ($newCall->phone_number_id) {
            echo "  ✅ Phone Number ID: {$newCall->phone_number_id} (ERFOLG!)\n";
        } else {
            echo "  ❌ Phone Number ID: NULL (FEHLER - Fix hat nicht gegriffen!)\n";
        }

        // Company Check
        if ($newCall->company_id) {
            echo "  ✅ Company ID:      {$newCall->company_id}";
            echo ($newCall->company_id == 15) ? " (AskProAI - KORREKT!)\n" : " (FALSCH!)\n";
        } else {
            echo "  ❌ Company ID:      NULL (FEHLER - Routing funktioniert nicht!)\n";
        }

        echo "\nWARTE AUF UPDATES (30 Sekunden)...\n";

        // Monitor for updates
        for ($j = 0; $j < 30; $j++) {
            $updatedCall = Call::find($newCall->id);

            if (!$newCall->phone_number_id && $updatedCall->phone_number_id) {
                echo "  ✅ UPDATE: Phone Number ID wurde gesetzt: {$updatedCall->phone_number_id}\n";
                $newCall = $updatedCall;
            }

            if (!$newCall->company_id && $updatedCall->company_id) {
                echo "  ✅ UPDATE: Company ID wurde gesetzt: {$updatedCall->company_id}\n";
                $newCall = $updatedCall;
            }

            if ($updatedCall->status != $newCall->status) {
                echo "  🔄 Status Update: {$newCall->status} → {$updatedCall->status}\n";
                $newCall = $updatedCall;
            }

            sleep(1);
            echo ".";
            flush();
        }

        // Final check
        echo "\n\n════════════════════════════════════════════════════════════════════\n";
        echo "ENDERGEBNIS:\n\n";

        $finalCall = Call::find($newCall->id);
        $success = $finalCall->phone_number_id && $finalCall->company_id == 15;

        if ($success) {
            echo "✅ ✅ ✅ ERFOLGREICH! ✅ ✅ ✅\n\n";
            echo "Phone Number wurde korrekt verknüpft!\n";
            echo "Company ID 15 (AskProAI) wurde erkannt!\n";
            echo "Das Admin Portal Routing funktioniert jetzt!\n";
        } else {
            echo "❌ PROBLEM BESTEHT WEITERHIN! ❌\n\n";
            if (!$finalCall->phone_number_id) {
                echo "Phone Number ID fehlt immer noch.\n";
            }
            if ($finalCall->company_id != 15) {
                echo "Company ID ist falsch: {$finalCall->company_id}\n";
            }
        }

        break;
    }

    echo ".";
    flush();
    sleep(1);
}

if (!isset($newCall)) {
    echo "\n\n❌ KEIN ANRUF ERKANNT nach 120 Sekunden!\n";
    echo "Bitte versuchen Sie es erneut.\n";
}

echo "\n════════════════════════════════════════════════════════════════════\n";
echo "Monitoring beendet: " . date('H:i:s') . "\n";
echo "════════════════════════════════════════════════════════════════════\n\n";