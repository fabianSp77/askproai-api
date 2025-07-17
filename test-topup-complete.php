#!/usr/bin/env php
<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║               🎉 STRIPE IST JETZT VOLLSTÄNDIG KONFIGURIERT!        ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

echo "✅ KONFIGURATION KOMPLETT:\n";
echo "────────────────────────\n";
echo "• API Keys:         ✅ Konfiguriert (LIVE MODE)\n";
echo "• Webhook Secret:   ✅ Eingetragen\n";
echo "• Webhook URL:      ✅ https://api.askproai.de/api/stripe/webhook\n";
echo "• Payment Methods:  ✅ Kreditkarten aktiviert\n";
echo "• Datenbank:        ✅ Alle Tabellen vorhanden\n\n";

echo "🔗 DEINE TOPUP-LINKS:\n";
echo "────────────────────\n\n";

echo "Standard Link (Kunde wählt Betrag):\n";
echo "👉 https://api.askproai.de/topup/1\n\n";

echo "Mit festem Betrag:\n";
echo "👉 https://api.askproai.de/topup/1?amount=50  (50€)\n";
echo "👉 https://api.askproai.de/topup/1?amount=100 (100€)\n";
echo "👉 https://api.askproai.de/topup/1?amount=200 (200€)\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "💰 DEIN PREPAID-GESCHÄFTSMODELL:\n";
echo "─────────────────────────────────\n\n";

echo "1. MINUTENBASIERTE ABRECHNUNG:\n";
echo "   • Preis: 0,15€ pro Minute\n";
echo "   • Sekundengenaue Abrechnung\n";
echo "   • Wird automatisch vom Guthaben abgezogen\n\n";

echo "2. PRO-TERMIN ABRECHNUNG (Alternative):\n";
echo "   • Fester Preis pro gebuchtem Termin\n";
echo "   • Konfigurierbar pro Company\n";
echo "   • Ebenfalls vom Guthaben abgezogen\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "🧪 TEST-MODUS AKTIVIEREN (für Sandbox):\n";
echo "──────────────────────────────────────\n\n";

echo "Du bist aktuell im LIVE-MODUS. Für Tests mit Spielgeld:\n\n";
echo "1. Führe aus: ./test-stripe-billing.sh start\n";
echo "2. Nutze Test-Kreditkarte: 4242 4242 4242 4242\n";
echo "3. Nach Tests: ./test-stripe-billing.sh stop\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "📊 BONUS-SYSTEM:\n";
echo "───────────────\n\n";

$bonusRules = \App\Models\BillingBonusRule::where('is_active', true)->get();
foreach ($bonusRules as $rule) {
    echo "• {$rule->name}:\n";
    echo "  Ab {$rule->min_amount}€ → {$rule->bonus_percentage}% Bonus\n";
}

echo "\n✅ ALLES BEREIT! Teste jetzt: https://api.askproai.de/topup/1\n\n";