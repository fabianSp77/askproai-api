<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 FRISEUR 1 AGENT - SERVICE-LISTE UPDATE\n";
echo "=========================================\n\n";

// Get all active services
$services = DB::table('services')
    ->where('company_id', 1)
    ->where('is_active', true)
    ->orderBy('name')
    ->get(['name', 'price', 'duration_minutes']);

echo "✅ {$services->count()} Services gefunden\n\n";

// Build service list for general prompt
$serviceListText = "**VERFÜGBARE DIENSTLEISTUNGEN:**\n\n";
foreach ($services as $service) {
    $serviceListText .= sprintf(
        "- **%s** (%.2f EUR, %d Minuten)\n",
        $service->name,
        $service->price,
        $service->duration_minutes
    );
}

$serviceListText .= "\n**WICHTIG:** Dies sind ALLE verfügbaren Dienstleistungen. ";
$serviceListText .= "Sage NIEMALS 'Wir bieten [X] nicht an', ohne vorher diese Liste geprüft oder das Backend gefragt zu haben. ";
$serviceListText .= "Bei unklaren Service-Namen: Frage das Backend über die Function Calls oder biete ähnliche Services aus dieser Liste an.\n\n";
$serviceListText .= "**SYNONYME & VARIANTEN:** Kunden verwenden oft alternative Bezeichnungen:\n";
$serviceListText .= "- 'Hair Detox' oder 'Detox' → Hairdetox\n";
$serviceListText .= "- 'Herrenschnitt' oder 'Männerhaarschnitt' → Herrenhaarschnitt\n";
$serviceListText .= "- 'Strähnchen' oder 'Highlights' → Balayage/Ombré\n";
$serviceListText .= "- 'Locken' → Dauerwelle\n";
$serviceListText .= "- 'Blondierung' → Komplette Umfärbung (Blondierung)\n";
$serviceListText .= "- 'Olaplex' → Rebuild Treatment Olaplex\n\n";
$serviceListText .= "Bei unsicheren Service-Namen: Nutze check_availability_v17 oder frage nach, welcher Service gemeint ist.\n";

echo "=== GENERIERTE SERVICE-LISTE ===\n";
echo $serviceListText;
echo "\n=== ENDE SERVICE-LISTE ===\n\n";

// Save to file for manual integration
$outputFile = __DIR__ . '/../AGENT_SERVICE_LIST_UPDATE.txt';
file_put_contents($outputFile, $serviceListText);

echo "✅ Service-Liste gespeichert: $outputFile\n\n";

echo "📋 NÄCHSTE SCHRITTE:\n\n";
echo "1. ⚠️ Seeder ausführen (WICHTIG!):\n";
echo "   php artisan db:seed --class=Friseur1ServiceSynonymsSeeder --force\n";
echo "   → Fügt ~150 Synonyme hinzu, inkl. 'Hair Detox' für 'Hairdetox'\n\n";

echo "2. 🔧 Agent General Prompt aktualisieren:\n";
echo "   a) Gehe zu: https://app.retellai.com/\n";
echo "   b) Öffne Agent: Friseur1 Fixed V2\n";
echo "   c) Bearbeite 'General Prompt'\n";
echo "   d) Füge die Service-Liste aus AGENT_SERVICE_LIST_UPDATE.txt hinzu\n";
echo "   e) Speichere und veröffentliche neue Version\n\n";

echo "3. 📝 Conversation Flow anpassen (Optional):\n";
echo "   - Node 'Intent Erkennung': Nicht sofort ablehnen\n";
echo "   - Stattdessen: Backend nach ähnlichen Services fragen\n";
echo "   - Oder: Aus der Service-Liste Vorschläge machen\n\n";

echo "4. 🧪 Testen:\n";
echo "   - Test 1: 'Hair Detox' → Sollte Hairdetox erkennen\n";
echo "   - Test 2: 'Herrenschnitt' → Sollte Herrenhaarschnitt erkennen\n";
echo "   - Test 3: 'Strähnchen' → Sollte Balayage/Ombré erkennen\n\n";

echo "⚠️ PROBLEM IDENTIFIZIERT:\n";
echo "Der Agent lehnt Services ab, OHNE das Backend zu fragen.\n";
echo "Er sollte IMMER entweder:\n";
echo "  1. Die Service-Liste prüfen\n";
echo "  2. Das Backend fragen (check_availability_v17)\n";
echo "  3. Ähnliche Services vorschlagen\n\n";

echo "NIEMALS einfach sagen: 'Wir bieten [X] nicht an'!\n";
