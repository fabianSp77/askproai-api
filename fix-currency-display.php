<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Währungsanzeige Korrektur ===\n\n";

// 1. Prüfe PrepaidBalance Currency
$company = App\Models\Company::find(1);
$balance = $company->prepaidBalance;

echo "Company: " . $company->name . "\n";
echo "Current Balance: " . $balance->current_balance . " " . $balance->currency . "\n";
echo "Total Balance: " . $balance->getTotalBalance() . " " . $balance->currency . "\n\n";

// 2. Korrigiere Currency falls nötig
if (empty($balance->currency) || $balance->currency !== 'EUR') {
    $oldCurrency = $balance->currency;
    $balance->currency = 'EUR';
    $balance->save();
    echo "✅ Currency korrigiert: '$oldCurrency' → 'EUR'\n";
} else {
    echo "✅ Currency ist bereits EUR\n";
}

// 3. Prüfe Billing Rate
$billingRate = $company->billingRate;
if ($billingRate) {
    echo "\nBilling Rate Currency: " . $billingRate->currency . "\n";
    if (empty($billingRate->currency) || $billingRate->currency !== 'EUR') {
        $oldCurrency = $billingRate->currency;
        $billingRate->currency = 'EUR';
        $billingRate->save();
        echo "✅ Billing Rate Currency korrigiert: '$oldCurrency' → 'EUR'\n";
    }
}

// 4. Prüfe letzte Transaktionen
echo "\nLetzte Transaktionen:\n";
$transactions = App\Models\BalanceTransaction::where('company_id', 1)
    ->whereNull('currency')
    ->orWhere('currency', '!=', 'EUR')
    ->get();

foreach ($transactions as $transaction) {
    $oldCurrency = $transaction->currency;
    $transaction->currency = 'EUR';
    $transaction->save();
    echo "- Transaction #" . $transaction->id . " korrigiert: '$oldCurrency' → 'EUR'\n";
}

// 5. Webhook für Stripe konfigurieren
echo "\n=== Stripe Webhook Status ===\n";
echo "Webhook URL: https://api.askproai.de/api/stripe/webhook\n";
echo "Bitte stellen Sie sicher, dass dieser Webhook in Ihrem Stripe Dashboard konfiguriert ist.\n";
echo "Events die aktiviert sein sollten:\n";
echo "- checkout.session.completed\n";
echo "- payment_intent.succeeded\n";
echo "- payment_intent.payment_failed\n";

echo "\n✅ Währungskorrektur abgeschlossen!\n";