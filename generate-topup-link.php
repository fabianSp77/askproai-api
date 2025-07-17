#!/usr/bin/env php
<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║           PUBLIC TOPUP LINKS - FERTIG ZUM VERSENDEN!               ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

$company = Company::find(1);

if (!$company) {
    echo "❌ Keine Company gefunden!\n";
    exit(1);
}

echo "✅ Company: {$company->name}\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$baseUrl = 'https://api.askproai.de/topup/' . $company->id;

echo "📧 EMAIL-VORLAGE FÜR KUNDEN:\n";
echo "────────────────────────────\n\n";

echo "Betreff: Guthaben aufladen für {$company->name}\n\n";

echo "Sehr geehrter Kunde,\n\n";
echo "Sie können Ihr Guthaben bei uns ganz einfach online aufladen.\n";
echo "Klicken Sie dazu einfach auf einen der folgenden Links:\n\n";
echo "➤ Guthaben aufladen (Betrag selbst wählen):\n";
echo "   $baseUrl\n\n";
echo "➤ Oder wählen Sie einen festen Betrag:\n";
echo "   • 50 EUR:  {$baseUrl}?amount=50\n";
echo "   • 100 EUR: {$baseUrl}?amount=100\n";
echo "   • 200 EUR: {$baseUrl}?amount=200\n\n";
echo "Die Zahlung erfolgt sicher über Stripe.\n";
echo "Nach erfolgreicher Zahlung wird Ihr Guthaben sofort aufgeladen.\n\n";
echo "Mit freundlichen Grüßen\n";
echo "{$company->name}\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "💬 WHATSAPP-NACHRICHT:\n";
echo "──────────────────────\n\n";

echo "Hallo! 👋\n\n";
echo "Hier ist Ihr Link zum Guthaben aufladen:\n";
echo "$baseUrl\n\n";
echo "Oder direkt 100€ aufladen:\n";
echo "{$baseUrl}?amount=100\n\n";
echo "Die Zahlung ist sicher und Ihr Guthaben wird sofort aufgeladen. ✅\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "📱 QR-CODE (für Druck/Aushang):\n";
echo "───────────────────────────────\n\n";

$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($baseUrl);
echo "QR-Code generieren: $qrUrl\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Check Stripe mode
$stripeKey = config('services.stripe.publishable');
if (strpos($stripeKey, 'pk_test_') === 0) {
    echo "ℹ️  Info: Stripe ist im TEST-MODUS\n";
    echo "   Test-Kreditkarte: 4242 4242 4242 4242\n";
} else {
    echo "⚠️  ACHTUNG: Stripe ist im LIVE-MODUS!\n";
    echo "   Echte Zahlungen werden durchgeführt.\n";
}

echo "\n✅ Links sind sofort einsatzbereit!\n\n";