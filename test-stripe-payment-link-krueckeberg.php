<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Company;
use App\Services\StripeTopupService;
use Illuminate\Support\Facades\Log;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Stripe Payment Link Test für Krückeberg GmbH ===\n\n";

try {
    // Finde die Krückeberg GmbH
    $company = Company::where('name', 'LIKE', '%Krückeberg%')
        ->orWhere('name', 'LIKE', '%Krueckeberg%')
        ->first();
    
    if (!$company) {
        echo "❌ Krückeberg GmbH nicht gefunden!\n";
        echo "Verfügbare Companies:\n";
        Company::select('id', 'name')->limit(10)->get()->each(function($c) {
            echo "  - ID: {$c->id}, Name: {$c->name}\n";
        });
        exit(1);
    }
    
    echo "✅ Company gefunden: {$company->name} (ID: {$company->id})\n";
    echo "   Email: {$company->email}\n";
    echo "   Telefon: {$company->phone}\n\n";
    
    // Stripe Service initialisieren
    $stripeService = app(StripeTopupService::class);
    
    // Prüfe ob bereits ein Payment Link existiert
    $metadata = $company->metadata ?? [];
    if (isset($metadata['stripe_payment_link_url'])) {
        echo "ℹ️  Bestehender Payment Link gefunden:\n";
        echo "   URL: {$metadata['stripe_payment_link_url']}\n";
        echo "   ID: {$metadata['stripe_payment_link_id']}\n";
        echo "   Erstellt: {$metadata['stripe_payment_link_created_at']}\n\n";
        
        echo "Neuen Payment Link erstellen? (j/n): ";
        $input = trim(fgets(STDIN));
        if (strtolower($input) !== 'j') {
            echo "\n✅ Verwende bestehenden Payment Link.\n";
            echo "🔗 Payment Link: {$metadata['stripe_payment_link_url']}\n";
            exit(0);
        }
    }
    
    // Payment Link Optionen
    echo "\n📋 Payment Link Optionen:\n";
    echo "1. Fester Betrag (z.B. 100€)\n";
    echo "2. Variabler Betrag (Kunde kann Betrag eingeben)\n";
    echo "Wähle Option (1 oder 2): ";
    $option = trim(fgets(STDIN));
    
    $amount = null;
    if ($option === '1') {
        echo "Betrag in EUR eingeben (z.B. 100): ";
        $amount = floatval(trim(fgets(STDIN)));
        if ($amount < 10 || $amount > 5000) {
            echo "❌ Ungültiger Betrag! Muss zwischen 10€ und 5000€ liegen.\n";
            exit(1);
        }
    }
    
    echo "\n🔄 Erstelle Stripe Payment Link...\n";
    
    // Payment Link erstellen
    $paymentLinkUrl = $stripeService->createPaymentLink(
        $company,
        $amount,
        [
            'created_by' => 'test-script',
            'purpose' => 'manual-topup',
        ]
    );
    
    if (!$paymentLinkUrl) {
        echo "❌ Fehler beim Erstellen des Payment Links!\n";
        
        // Check recent log entries
        $logFile = __DIR__ . '/storage/logs/laravel.log';
        if (file_exists($logFile)) {
            $lines = file($logFile);
            $recentLines = array_slice($lines, -20);
            foreach ($recentLines as $line) {
                if (stripos($line, 'stripe') !== false || stripos($line, 'payment') !== false) {
                    echo "Log: " . trim($line) . "\n";
                }
            }
        }
        
        exit(1);
    }
    
    echo "\n✅ Payment Link erfolgreich erstellt!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🔗 Payment Link URL:\n";
    echo "   {$paymentLinkUrl}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    if ($amount) {
        echo "💰 Betrag: " . number_format($amount, 2, ',', '.') . " €\n";
    } else {
        echo "💰 Betrag: Variabel (10€ - 5.000€)\n";
    }
    
    echo "\n📱 QR-Code URL:\n";
    $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query([
        'size' => '300x300',
        'data' => $paymentLinkUrl,
        'format' => 'png',
    ]);
    echo "   {$qrCodeUrl}\n";
    
    echo "\n📧 Dieser Link kann nun an Kunden verschickt werden:\n";
    echo "   - Per E-Mail\n";
    echo "   - Per WhatsApp\n";
    echo "   - Als QR-Code ausgedruckt\n";
    echo "   - Auf der Website eingebettet\n";
    
    echo "\n✅ Der Link ist dauerhaft gültig und kann mehrfach verwendet werden!\n";
    
    // Test-Zahlung Option
    echo "\n🧪 Möchten Sie eine Test-Zahlung durchführen? (j/n): ";
    $testPayment = trim(fgets(STDIN));
    if (strtolower($testPayment) === 'j') {
        echo "\n📋 Test-Kreditkarten:\n";
        echo "   Erfolg: 4242 4242 4242 4242\n";
        echo "   3D Secure: 4000 0025 0000 3155\n";
        echo "   Ablehnung: 4000 0000 0000 0002\n";
        echo "\nÖffne den Payment Link in deinem Browser und teste die Zahlung.\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✅ Test abgeschlossen.\n";