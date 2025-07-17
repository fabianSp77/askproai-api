#!/usr/bin/env php
<?php

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║                    STRIPE WEBHOOK CONFIGURATION                     ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

echo "📝 SCHRITT 1: Webhook in Stripe erstellen\n";
echo "─────────────────────────────────────────\n\n";

echo "1. Öffne: https://dashboard.stripe.com/webhooks\n";
echo "2. Klicke auf '+ Add endpoint'\n";
echo "3. Fülle aus:\n\n";

echo "   Endpoint URL:\n";
echo "   ┌─────────────────────────────────────────────────┐\n";
echo "   │ https://api.askproai.de/api/stripe/webhook     │\n";
echo "   └─────────────────────────────────────────────────┘\n\n";

echo "4. Events to listen for (wähle diese aus):\n";
echo "   ✓ checkout.session.completed\n";
echo "   ✓ payment_intent.succeeded\n";
echo "   ✓ payment_intent.payment_failed\n";
echo "   ✓ charge.succeeded (optional)\n";
echo "   ✓ charge.failed (optional)\n\n";

echo "5. Klicke 'Add endpoint'\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "📝 SCHRITT 2: Webhook Secret kopieren\n";
echo "────────────────────────────────────\n\n";

echo "1. Nach dem Erstellen, klicke auf den Webhook\n";
echo "2. Unter 'Signing secret' klicke 'Reveal'\n";
echo "3. Kopiere den Secret (beginnt mit whsec_)\n";
echo "4. Füge ihn hier ein:\n\n";

echo "Webhook Secret eingeben (oder Enter für später): ";
$webhookSecret = trim(fgets(STDIN));

if ($webhookSecret && strpos($webhookSecret, 'whsec_') === 0) {
    $envFile = '/var/www/api-gateway/.env';
    $envContent = file_get_contents($envFile);
    
    // Update or add webhook secret
    if (strpos($envContent, 'STRIPE_WEBHOOK_SECRET=') !== false) {
        $envContent = preg_replace('/STRIPE_WEBHOOK_SECRET=.*/', 'STRIPE_WEBHOOK_SECRET=' . $webhookSecret, $envContent);
    } else {
        // Find Stripe section and add it there
        $envContent = preg_replace(
            '/(STRIPE_SECRET=.*\n)/',
            "$1STRIPE_WEBHOOK_SECRET=$webhookSecret\n",
            $envContent
        );
    }
    
    file_put_contents($envFile, $envContent);
    
    echo "\n✅ Webhook Secret wurde in .env gespeichert!\n";
    
    // Clear config cache
    exec('cd /var/www/api-gateway && php artisan config:clear');
    echo "✅ Config Cache geleert\n";
} else if ($webhookSecret) {
    echo "\n❌ Ungültiges Format. Webhook Secret muss mit 'whsec_' beginnen.\n";
} else {
    echo "\n⏭️  Übersprungen. Bitte manuell in .env eintragen:\n";
    echo "STRIPE_WEBHOOK_SECRET=whsec_dein_secret_hier\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "📝 SCHRITT 3: Test-Modus aktivieren (für Tests)\n";
echo "──────────────────────────────────────────────\n\n";

echo "Möchtest du in den Test-Modus wechseln? (j/n): ";
$testMode = strtolower(trim(fgets(STDIN)));

if ($testMode === 'j' || $testMode === 'y') {
    exec('cd /var/www/api-gateway && ./test-stripe-billing.sh start', $output);
    echo implode("\n", $output) . "\n";
} else {
    echo "\nDu bleibst im LIVE-Modus. Für Tests später ausführen:\n";
    echo "./test-stripe-billing.sh start\n";
}

echo "\n✅ Fertig! Teste jetzt deinen Topup-Link:\n";
echo "   https://api.askproai.de/topup/1\n\n";