<?php

/**
 * Set realistic market prices for hair salon services
 * Based on German market standards 2025
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════════\n";
echo "Marktgerechte Preise setzen - Deutsche Friseur-Standards 2025\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Marktgerechte Preise (mittleres bis gehobenes Preisniveau)
$prices = [
    // Haarschnitte
    438 => 32.00,  // Herrenhaarschnitt
    436 => 45.00,  // Damenhaarschnitt
    434 => 20.00,  // Kinderhaarschnitt
    435 => 30.00,  // Trockenschnitt

    // Waschen & Styling
    439 => 55.00,  // Waschen, schneiden, föhnen
    437 => 28.00,  // Waschen & Styling
    430 => 20.00,  // Föhnen & Styling Herren
    431 => 32.00,  // Föhnen & Styling Damen

    // Färbe-Services (Composite)
    440 => 58.00,  // Ansatzfärbung
    442 => 85.00,  // Ansatz + Längenausgleich
    444 => 145.00, // Komplette Umfärbung (Blondierung)
    443 => 110.00, // Balayage/Ombré
    441 => 78.00,  // Dauerwelle

    // Spezial-Services
    432 => 38.00,  // Gloss
    433 => 28.00,  // Haarspende

    // Behandlungen (KORREKTUR - waren viel zu hoch!)
    41 => 22.00,   // Hairdetox (war 150€!)
    42 => 28.00,   // Intensiv Pflege Maria Nila (war 200€!)
    43 => 42.00,   // Rebuild Treatment Olaplex (war 350€!)
];

echo "📋 Preise werden gesetzt...\n\n";

$updated = 0;
$errors = 0;

foreach ($prices as $serviceId => $price) {
    $service = DB::table('services')->where('id', $serviceId)->first();

    if (!$service) {
        echo "⚠️  Service ID {$serviceId} nicht gefunden\n";
        $errors++;
        continue;
    }

    $oldPrice = $service->price ?? 0;

    DB::table('services')
        ->where('id', $serviceId)
        ->update([
            'price' => $price,
            'updated_at' => now()
        ]);

    $priceChange = $oldPrice > 0 ? ' (war: ' . number_format($oldPrice, 2, ',', '.') . '€)' : ' (neu)';
    echo "✅ ID {$serviceId}: " . substr($service->name, 0, 35) . " → " . number_format($price, 2, ',', '.') . "€" . $priceChange . "\n";

    $updated++;
}

echo "\n" . str_repeat("─", 63) . "\n";
echo "📊 ZUSAMMENFASSUNG:\n";
echo "  ✅ Preise gesetzt: {$updated}\n";
echo "  ❌ Fehler: {$errors}\n";
echo "\n";

// Verifikation
echo "🔍 VERIFIKATION:\n\n";

$services = DB::table('services')
    ->where('company_id', 1)
    ->whereNotNull('calcom_event_type_id')
    ->orderBy('price', 'desc')
    ->get(['id', 'name', 'price', 'duration_minutes', 'composite']);

$noPrice = 0;

foreach ($services as $svc) {
    $icon = $svc->composite ? '🎨' : '  ';
    $priceDisplay = $svc->price > 0 ? number_format($svc->price, 2, ',', '.') . '€' : '❌ FEHLT';

    if ($svc->price <= 0) {
        $noPrice++;
        echo "❌ " . $icon . " ID " . $svc->id . ": " . $svc->name . " → PREIS FEHLT\n";
    } else {
        echo "✅ " . $icon . " ID " . $svc->id . ": " . substr($svc->name, 0, 35) . " → " . $priceDisplay . "\n";
    }
}

echo "\n" . str_repeat("─", 63) . "\n";

if ($noPrice === 0) {
    echo "✅ PERFEKT! Alle Services haben Preise.\n";
} else {
    echo "⚠️  {$noPrice} Services haben noch keine Preise.\n";
}

echo "\n" . str_repeat("═", 63) . "\n";
echo "\n📝 PREIS-ERKLÄRUNG:\n\n";

echo "Haarschnitte:\n";
echo "  • Herrenhaarschnitt: 32€\n";
echo "  • Damenhaarschnitt: 45€\n";
echo "  • Kinderhaarschnitt: 20€\n";
echo "  • Trockenschnitt: 30€\n\n";

echo "Waschen & Styling:\n";
echo "  • Waschen, schneiden, föhnen: 55€\n";
echo "  • Waschen & Styling: 28€\n";
echo "  • Föhnen & Styling (Herren): 20€\n";
echo "  • Föhnen & Styling (Damen): 32€\n\n";

echo "Färbe-Services (Composite):\n";
echo "  • Ansatzfärbung: 58€ (160 min)\n";
echo "  • Ansatz + Längenausgleich: 85€ (170 min)\n";
echo "  • Komplette Blondierung: 145€ (220 min)\n";
echo "  • Balayage/Ombré: 110€ (150 min)\n";
echo "  • Dauerwelle: 78€ (115 min)\n\n";

echo "Spezial-Services:\n";
echo "  • Gloss: 38€\n";
echo "  • Haarspende: 28€\n\n";

echo "Behandlungen (KORRIGIERT):\n";
echo "  • Hairdetox: 22€ (war 150€ ❌)\n";
echo "  • Intensiv Pflege: 28€ (war 200€ ❌)\n";
echo "  • Rebuild Treatment Olaplex: 42€ (war 350€ ❌)\n\n";

echo "💡 Diese Preise entsprechen dem gehobenen Mittelklasse-Segment\n";
echo "   für Friseure in deutschen Großstädten (2025).\n\n";
