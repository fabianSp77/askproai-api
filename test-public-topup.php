#!/usr/bin/env php
<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;

echo "\n=== Public Topup Link Generator ===\n\n";

// Lade die erste aktive Company
$company = Company::where('is_active', true)->first();

if (!$company) {
    $company = Company::first();
}

if (!$company) {
    echo "FEHLER: Keine Company in der Datenbank gefunden!\n";
    exit(1);
}

echo "Company gefunden:\n";
echo "- ID: {$company->id}\n";
echo "- Name: {$company->name}\n";
echo "- E-Mail: {$company->email}\n";
echo "\n";

$baseUrl = 'https://api.askproai.de';
$topupUrl = "{$baseUrl}/topup/{$company->id}";

echo "=== TOPUP LINKS ===\n\n";
echo "1. Standard Link (Kunde wählt Betrag):\n";
echo "   {$topupUrl}\n\n";

echo "2. Link mit vorgegebenem Betrag:\n";
echo "   - 50€:  {$topupUrl}?amount=50\n";
echo "   - 100€: {$topupUrl}?amount=100\n";
echo "   - 200€: {$topupUrl}?amount=200\n";
echo "   - 500€: {$topupUrl}?amount=500\n\n";

echo "=== TEST ANLEITUNG ===\n\n";
echo "1. Öffne einen der Links im Browser\n";
echo "2. Gib Name und E-Mail ein\n";
echo "3. Wähle einen Betrag (oder nutze den vorgegebenen)\n";
echo "4. Du wirst zu Stripe weitergeleitet\n";
echo "5. Nutze Test-Kreditkarte: 4242 4242 4242 4242\n";
echo "   - Ablaufdatum: Beliebiges zukünftiges Datum\n";
echo "   - CVC: Beliebige 3 Ziffern\n";
echo "   - Postleitzahl: Beliebige 5 Ziffern\n\n";

// Prüfe Stripe-Konfiguration
$stripeKey = config('services.stripe.key');
$stripeSecret = config('services.stripe.secret');

if (strpos($stripeKey, 'pk_test_') === 0) {
    echo "✅ Stripe ist im TEST-MODUS\n";
} elseif (strpos($stripeKey, 'pk_live_') === 0) {
    echo "⚠️  WARNUNG: Stripe ist im LIVE-MODUS!\n";
    echo "   Für Tests solltest du den Test-Modus aktivieren:\n";
    echo "   ./test-stripe-billing.sh start\n";
} else {
    echo "❌ Stripe ist nicht konfiguriert!\n";
}

echo "\n";

// Generiere QR-Code URL für einfaches Teilen
echo "=== QR-CODE GENERATOR ===\n\n";
echo "Für QR-Code, öffne:\n";
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($topupUrl);
echo $qrUrl . "\n\n";

echo "=== CURL TEST-BEFEHL ===\n\n";
echo "curl -X POST {$baseUrl}/api/generate-topup-link \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -d '{\"company_id\": {$company->id}, \"amount\": 100}'\n\n";

echo "Fertig!\n";