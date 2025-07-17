#!/usr/bin/env php
<?php

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║                   STRIPE CONFIGURATION CHECKLIST                    ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

// 1. Check API Keys
echo "1️⃣  API KEYS STATUS:\n";
echo "────────────────────\n";

$publicKey = config('services.stripe.publishable');
$secretKey = config('services.stripe.secret');
$webhookSecret = config('services.stripe.webhook_secret');

if ($publicKey) {
    $keyType = strpos($publicKey, 'pk_test_') === 0 ? 'TEST' : 'LIVE';
    echo "✅ Public Key: " . substr($publicKey, 0, 20) . "... ($keyType MODE)\n";
} else {
    echo "❌ Public Key: NOT SET\n";
}

if ($secretKey) {
    $keyType = strpos($secretKey, 'sk_test_') === 0 ? 'TEST' : 'LIVE';
    echo "✅ Secret Key: " . substr($secretKey, 0, 20) . "... ($keyType MODE)\n";
} else {
    echo "❌ Secret Key: NOT SET\n";
}

if ($webhookSecret) {
    echo "✅ Webhook Secret: " . substr($webhookSecret, 0, 20) . "...\n";
} else {
    echo "⚠️  Webhook Secret: NOT SET (Webhooks won't work)\n";
}

// 2. Test API Connection
echo "\n2️⃣  API CONNECTION TEST:\n";
echo "───────────────────────\n";

if ($secretKey) {
    try {
        \Stripe\Stripe::setApiKey($secretKey);
        $account = \Stripe\Account::retrieve();
        echo "✅ Connected to Stripe Account: " . $account->email . "\n";
        echo "   Business Name: " . ($account->business_profile->name ?? 'Not set') . "\n";
        echo "   Country: " . $account->country . "\n";
        echo "   Default Currency: " . $account->default_currency . "\n";
    } catch (\Exception $e) {
        echo "❌ Connection Failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "⏭️  Skipped (no API key)\n";
}

// 3. Check Payment Methods
echo "\n3️⃣  PAYMENT METHODS:\n";
echo "──────────────────\n";

if ($secretKey) {
    try {
        $paymentMethodConfig = \Stripe\Account::retrieve()->capabilities;
        $methods = [
            'card_payments' => 'Kreditkarten',
            'sepa_debit_payments' => 'SEPA Lastschrift',
            'sofort_payments' => 'Sofort',
            'giropay_payments' => 'Giropay'
        ];
        
        foreach ($methods as $key => $name) {
            if (isset($paymentMethodConfig->$key)) {
                $status = $paymentMethodConfig->$key;
                if ($status === 'active') {
                    echo "✅ $name: Aktiviert\n";
                } else {
                    echo "⚠️  $name: $status\n";
                }
            } else {
                echo "❌ $name: Nicht verfügbar\n";
            }
        }
    } catch (\Exception $e) {
        echo "❌ Konnte Payment Methods nicht prüfen\n";
    }
}

// 4. Database Check
echo "\n4️⃣  DATABASE TABLES:\n";
echo "──────────────────\n";

$tables = [
    'balance_topups' => 'Aufladungen',
    'prepaid_balances' => 'Guthaben',
    'balance_transactions' => 'Transaktionen',
    'call_charges' => 'Anruf-Kosten'
];

foreach ($tables as $table => $name) {
    if (\Schema::hasTable($table)) {
        $count = \DB::table($table)->count();
        echo "✅ $name ($table): $count Einträge\n";
    } else {
        echo "❌ $name ($table): Tabelle fehlt!\n";
    }
}

// 5. Test Checkout Session
echo "\n5️⃣  TEST CHECKOUT SESSION:\n";
echo "─────────────────────────\n";

if ($secretKey) {
    try {
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Test Aufladung',
                    ],
                    'unit_amount' => 1000, // 10€
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => 'https://api.askproai.de/success',
            'cancel_url' => 'https://api.askproai.de/cancel',
        ]);
        
        echo "✅ Test Checkout Session erstellt!\n";
        echo "   Session ID: " . $session->id . "\n";
        echo "   Test URL: " . $session->url . "\n";
    } catch (\Exception $e) {
        echo "❌ Fehler: " . $e->getMessage() . "\n";
    }
}

// 6. Environment Check
echo "\n6️⃣  ENVIRONMENT:\n";
echo "───────────────\n";

echo "APP_ENV: " . config('app.env') . "\n";
echo "APP_DEBUG: " . (config('app.debug') ? 'true' : 'false') . "\n";
echo "APP_URL: " . config('app.url') . "\n";

// Summary
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📋 NÄCHSTE SCHRITTE:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

if (!$webhookSecret) {
    echo "1. Webhook in Stripe Dashboard erstellen:\n";
    echo "   URL: https://api.askproai.de/api/stripe/webhook\n";
    echo "   Events: checkout.session.completed, payment_intent.succeeded\n";
    echo "   Webhook Secret in .env eintragen\n\n";
}

if (!$secretKey || strpos($secretKey, 'sk_live_') === 0) {
    echo "2. Für Tests: Aktiviere Test-Modus\n";
    echo "   ./test-stripe-billing.sh start\n\n";
}

echo "3. Öffne STRIPE_SETUP_GUIDE.md für vollständige Anleitung\n\n";

echo "✅ Fertig!\n\n";