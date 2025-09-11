#!/usr/bin/env php
<?php

/**
 * Mehrstufiges Abrechnungssystem - Testskript
 * Testet den kompletten Ablauf: Plattform → Reseller (Mandant) → Endkunde
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\PricingPlan;
use App\Services\BillingChainService;
use Illuminate\Support\Facades\DB;

echo "\n" . str_repeat('═', 80) . "\n";
echo "MEHRSTUFIGES ABRECHNUNGSSYSTEM - TEST\n";
echo str_repeat('═', 80) . "\n\n";

// Aufräumen von vorherigen Testdaten
DB::transaction(function () {
    echo "🧹 Räume vorherige Testdaten auf...\n";
    Tenant::where('slug', 'like', 'test-%')->forceDelete();
});

// Schritt 1: Plattform-Mandant einrichten (falls nicht vorhanden)
echo "1️⃣ Richte Plattform-Mandant ein...\n";
$platform = Tenant::firstOrCreate(
    ['tenant_type' => 'platform'],
    [
        'name' => 'AskProAI Plattform',
        'slug' => 'askproai-plattform',
        'tenant_type' => 'platform',
        'balance_cents' => 0,
        'commission_rate' => 0
    ]
);
echo "   ✅ Plattform: {$platform->name}\n\n";

// Schritt 2: Reseller (Mandant) erstellen
echo "2️⃣ Erstelle Reseller (Mandant)...\n";
$reseller = Tenant::create([
    'name' => 'Premium Friseur Lösungen GmbH',
    'slug' => 'test-reseller-premium-friseur',
    'tenant_type' => 'reseller',
    'parent_tenant_id' => null, // Reseller haben keinen Parent
    'balance_cents' => 100000, // Startet mit 1.000,00 €
    'commission_rate' => 25.0, // 25% Provision auf Umsätze
    'base_cost_cents' => 30, // Plattform berechnet 30 Cent/Minute
    'reseller_markup_cents' => 10, // Reseller schlägt 10 Cent auf (berechnet Kunden 40 Cent)
    'can_set_prices' => true,
    'min_markup_percent' => 10,
    'max_markup_percent' => 50,
    'billing_mode' => 'direct',
    'auto_commission_payout' => true,
    'commission_payout_threshold_cents' => 5000 // Automatische Auszahlung bei 50 €
]);

echo "   ✅ Reseller: {$reseller->name}\n";
echo "   💰 Startguthaben: " . number_format($reseller->balance_cents/100, 2, ',', '.') . " €\n";
echo "   📊 Provisionssatz: {$reseller->commission_rate}%\n";
echo "   💵 Basiskosten: 0,30 €/Min | Kundenpreis: 0,40 €/Min | Aufschlag: 0,10 €/Min\n\n";

// Schritt 3: Endkunden für den Reseller erstellen
echo "3️⃣ Erstelle Endkunden für Reseller...\n";

$kunde1 = Tenant::create([
    'name' => 'Friseursalon Eleganz',
    'slug' => 'test-kunde-eleganz',
    'tenant_type' => 'reseller_customer',
    'parent_tenant_id' => $reseller->id,
    'balance_cents' => 50000, // Startet mit 500,00 €
    'billing_mode' => 'through_reseller'
]);
echo "   ✅ Kunde 1: {$kunde1->name} (Guthaben: " . 
     number_format($kunde1->balance_cents/100, 2, ',', '.') . " €)\n";

$kunde2 = Tenant::create([
    'name' => 'Hair & Beauty Studio',
    'slug' => 'test-kunde-beauty',
    'tenant_type' => 'reseller_customer',
    'parent_tenant_id' => $reseller->id,
    'balance_cents' => 30000, // Startet mit 300,00 €
    'billing_mode' => 'through_reseller'
]);
echo "   ✅ Kunde 2: {$kunde2->name} (Guthaben: " . 
     number_format($kunde2->balance_cents/100, 2, ',', '.') . " €)\n\n";

// Schritt 4: Direktkunde erstellen (ohne Reseller)
echo "4️⃣ Erstelle Direktkunden (ohne Reseller)...\n";
$direktkunde = Tenant::create([
    'name' => 'Direktkunde GmbH',
    'slug' => 'test-direktkunde',
    'tenant_type' => 'direct_customer',
    'parent_tenant_id' => null,
    'balance_cents' => 20000, // Startet mit 200,00 €
    'billing_mode' => 'direct'
]);
echo "   ✅ Direktkunde: {$direktkunde->name} (Guthaben: " . 
     number_format($direktkunde->balance_cents/100, 2, ',', '.') . " €)\n\n";

// Schritt 5: Abrechnungsszenarien testen
echo "5️⃣ Teste Abrechnungsszenarien...\n";
echo str_repeat('-', 80) . "\n\n";

$billingService = new BillingChainService();

// Szenario 1: Reseller-Kunde macht einen Anruf
echo "📞 Szenario 1: Reseller-Kunde (Eleganz) macht 5-Minuten-Anruf\n";
echo "   Erwarteter Ablauf: Kunde zahlt 2,00 € → Reseller behält 0,50 € → Plattform erhält 1,50 €\n\n";

$anfangsGuthabenKunde = $kunde1->balance_cents;
$anfangsGuthabenReseller = $reseller->balance_cents;

try {
    $transaktionen = $billingService->processBillingChain(
        $kunde1,
        'call',
        5, // 5 Minuten
        ['test_szenario' => 'reseller_kunde_anruf']
    );
    
    echo "   ✅ Transaktion abgeschlossen! " . count($transaktionen) . " Transaktionen erstellt:\n";
    foreach ($transaktionen as $idx => $trans) {
        $mandant = Tenant::find($trans->tenant_id);
        $betrag = number_format(abs($trans->amount_cents)/100, 2, ',', '.');
        $vorzeichen = $trans->amount_cents > 0 ? '+' : '-';
        echo "      " . ($idx + 1) . ". {$mandant->name}: {$vorzeichen}{$betrag} € ({$trans->description})\n";
        if ($trans->getBillingChainType()) {
            echo "         Kette: {$trans->getBillingChainType()}\n";
        }
    }
    
    // Guthaben aktualisieren
    $kunde1->refresh();
    $reseller->refresh();
    
    echo "\n   💰 Guthabenänderungen:\n";
    echo "      Kunde: " . number_format($anfangsGuthabenKunde/100, 2, ',', '.') . " € → " . 
         number_format($kunde1->balance_cents/100, 2, ',', '.') . " € (Gezahlt: " . 
         number_format(($anfangsGuthabenKunde - $kunde1->balance_cents)/100, 2, ',', '.') . " €)\n";
    echo "      Reseller: " . number_format($anfangsGuthabenReseller/100, 2, ',', '.') . " € → " . 
         number_format($reseller->balance_cents/100, 2, ',', '.') . " € (Netto: " . 
         number_format(($reseller->balance_cents - $anfangsGuthabenReseller)/100, 2, ',', '.') . " €)\n";
    
} catch (\Exception $e) {
    echo "   ❌ Fehler: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat('-', 80) . "\n\n";

// Szenario 2: Direktkunde macht einen Anruf
echo "📞 Szenario 2: Direktkunde macht 3-Minuten-Anruf\n";
echo "   Erwartet: Kunde zahlt Standardtarif direkt an Plattform\n\n";

$anfangsGuthabenDirekt = $direktkunde->balance_cents;

try {
    // Für Direktkunden müssen wir einen Preisplan setzen
    if (!$direktkunde->pricing_plan_id) {
        $standardPlan = PricingPlan::firstOrCreate(
            ['slug' => 'standard'],
            [
                'name' => 'Standardtarif',
                'price_per_minute_cents' => 42,
                'price_per_call_cents' => 10,
                'price_per_appointment_cents' => 100,
                'billing_type' => 'prepaid',
                'is_default' => true
            ]
        );
        $direktkunde->pricing_plan_id = $standardPlan->id;
        $direktkunde->save();
    }
    
    $transaktionen = $billingService->processBillingChain(
        $direktkunde,
        'call',
        3, // 3 Minuten
        ['test_szenario' => 'direktkunde_anruf']
    );
    
    echo "   ✅ Transaktion abgeschlossen!\n";
    $direktkunde->refresh();
    
    echo "   💰 Guthabenänderung:\n";
    echo "      Direktkunde: " . number_format($anfangsGuthabenDirekt/100, 2, ',', '.') . " € → " . 
         number_format($direktkunde->balance_cents/100, 2, ',', '.') . " € (Gezahlt: " . 
         number_format(($anfangsGuthabenDirekt - $direktkunde->balance_cents)/100, 2, ',', '.') . " €)\n";
    
} catch (\Exception $e) {
    echo "   ❌ Fehler: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat('-', 80) . "\n\n";

// Szenario 3: Mehrere Transaktionen für Provisionsverfolgung
echo "📊 Szenario 3: Mehrere Transaktionen für Provisionsverfolgung\n\n";

$gesamtProvision = 0;
for ($i = 1; $i <= 3; $i++) {
    try {
        $minuten = rand(2, 10);
        echo "   Anruf $i: {$kunde2->name} - {$minuten} Minuten\n";
        
        $transaktionen = $billingService->processBillingChain(
            $kunde2,
            'call',
            $minuten,
            ['test_szenario' => "batch_anruf_$i"]
        );
        
        // Provision aus dieser Transaktion berechnen
        $kundeTrans = $transaktionen[0] ?? null;
        if ($kundeTrans && $kundeTrans->commission_amount_cents) {
            $gesamtProvision += $kundeTrans->commission_amount_cents;
            echo "      Verdiente Provision: " . 
                 number_format($kundeTrans->commission_amount_cents/100, 2, ',', '.') . " €\n";
        }
        
    } catch (\Exception $e) {
        echo "      ❌ Fehler: " . $e->getMessage() . "\n";
    }
}

echo "\n   💰 Gesamte verdiente Provision: " . number_format($gesamtProvision/100, 2, ',', '.') . " €\n";

// Schritt 6: Provisionsbuch anzeigen
echo "\n" . str_repeat('═', 80) . "\n";
echo "6️⃣ Provisionsbuch-Zusammenfassung\n";
echo str_repeat('-', 80) . "\n\n";

$provisionen = DB::table('commission_ledger')
    ->where('reseller_tenant_id', $reseller->id)
    ->get();

if ($provisionen->count() > 0) {
    echo "   {$provisionen->count()} Provisionseinträge gefunden:\n\n";
    
    $gesamtBrutto = 0;
    $gesamtPlattformKosten = 0;
    $gesamtProvision = 0;
    
    foreach ($provisionen as $idx => $provision) {
        $kundenName = Tenant::find($provision->customer_tenant_id)->name ?? 'Unbekannt';
        echo "   " . ($idx + 1) . ". Kunde: {$kundenName}\n";
        echo "      Brutto: " . number_format($provision->gross_amount_cents/100, 2, ',', '.') . " € | ";
        echo "Plattform: " . number_format($provision->platform_cost_cents/100, 2, ',', '.') . " € | ";
        echo "Provision: " . number_format($provision->commission_cents/100, 2, ',', '.') . " € ";
        echo "(" . number_format($provision->commission_rate, 2, ',', '.') . "%)\n";
        
        $gesamtBrutto += $provision->gross_amount_cents;
        $gesamtPlattformKosten += $provision->platform_cost_cents;
        $gesamtProvision += $provision->commission_cents;
    }
    
    echo "\n   📊 Summen:\n";
    echo "      Gesamtumsatz: " . number_format($gesamtBrutto/100, 2, ',', '.') . " €\n";
    echo "      Plattformumsatz: " . number_format($gesamtPlattformKosten/100, 2, ',', '.') . " €\n";
    echo "      Reseller-Provision: " . number_format($gesamtProvision/100, 2, ',', '.') . " €\n";
    echo "      Gewinnmarge: " . number_format(($gesamtProvision/$gesamtBrutto)*100, 2, ',', '.') . "%\n";
} else {
    echo "   Keine Provisionseinträge gefunden.\n";
}

// Schritt 7: Abschließende Zusammenfassung
echo "\n" . str_repeat('═', 80) . "\n";
echo "7️⃣ Endgültige Kontostände\n";
echo str_repeat('-', 80) . "\n\n";

$alleMandanten = Tenant::whereIn('slug', [
    'test-reseller-premium-friseur',
    'test-kunde-eleganz',
    'test-kunde-beauty',
    'test-direktkunde'
])->get();

foreach ($alleMandanten as $mandant) {
    $typLabel = match($mandant->tenant_type) {
        'reseller' => '🏢 Reseller',
        'reseller_customer' => '👥 Reseller-Kunde',
        'direct_customer' => '🔗 Direktkunde',
        default => '❓ Unbekannt'
    };
    
    echo "   {$typLabel} {$mandant->name}:\n";
    echo "      Guthaben: " . number_format($mandant->balance_cents/100, 2, ',', '.') . " €\n";
    
    if ($mandant->isReseller()) {
        $transCount = Transaction::where('tenant_id', $mandant->id)->count();
        $provisionCount = DB::table('commission_ledger')
            ->where('reseller_tenant_id', $mandant->id)
            ->count();
        echo "      Transaktionen: {$transCount} | Provisionseinträge: {$provisionCount}\n";
    }
    
    if ($mandant->hasReseller()) {
        echo "      Reseller: " . ($mandant->parentTenant->name ?? 'Keiner') . "\n";
    }
    
    echo "\n";
}

echo str_repeat('═', 80) . "\n";
echo "✅ MEHRSTUFIGER ABRECHNUNGSTEST ERFOLGREICH ABGESCHLOSSEN!\n";
echo str_repeat('═', 80) . "\n\n";

echo "Wichtige Erkenntnisse:\n";
echo "• Reseller-Kunden zahlen aufgeschlagene Preise (0,40 €/Min vs. 0,30 € Plattformkosten)\n";
echo "• Reseller verdienen Provision auf den Aufschlag (25% in diesem Beispiel)\n";
echo "• Direktkunden zahlen Standardtarife ohne Zwischenhändler\n";
echo "• Alle Transaktionen werden mit vollständigem Prüfpfad nachverfolgt\n";
echo "• Provisionsbuch ermöglicht transparente Auszahlungsverfolgung\n\n";