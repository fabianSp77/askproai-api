#!/usr/bin/env php
<?php

/**
 * Produktions-Test für das Abrechnungssystem
 * 
 * Führt alle kritischen Tests durch um sicherzustellen,
 * dass das Billing-System produktionsbereit ist.
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\BalanceTopup;
use App\Models\PricingPlan;
use App\Services\BillingChainService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

// Farben für Ausgabe
function success($msg) { echo "\033[32m✓ $msg\033[0m\n"; }
function error($msg) { echo "\033[31m✗ $msg\033[0m\n"; }
function warning($msg) { echo "\033[33m⚠ $msg\033[0m\n"; }
function info($msg) { echo "\033[36mℹ $msg\033[0m\n"; }
function section($msg) { 
    echo "\n\033[1;34m" . str_repeat('=', 70) . "\033[0m\n";
    echo "\033[1;34m $msg\033[0m\n";
    echo "\033[1;34m" . str_repeat('=', 70) . "\033[0m\n\n";
}

$testResults = [];
$criticalErrors = [];
$warnings = [];

section("ABRECHNUNGSSYSTEM PRODUKTIONS-TEST");
info("Timestamp: " . date('Y-m-d H:i:s'));
info("Umgebung: " . app()->environment());
echo "\n";

// ===========================================================================
// TEST 1: Konfiguration
// ===========================================================================
section("1. KONFIGURATION PRÜFEN");

try {
    // Prüfe kritische Config-Werte
    $requiredConfigs = [
        'billing.enabled' => 'Billing-System aktiviert',
        'billing.stripe.secret' => 'Stripe Secret Key',
        'billing.stripe.webhook_secret' => 'Stripe Webhook Secret',
        'billing.pricing.platform.call_minutes' => 'Platform-Preis Anrufe',
        'billing.pricing.customer.call_minutes' => 'Kunden-Preis Anrufe',
    ];
    
    foreach ($requiredConfigs as $key => $description) {
        if (config($key)) {
            success("$description: Konfiguriert");
        } else {
            error("$description: FEHLT!");
            $criticalErrors[] = "Fehlende Konfiguration: $key";
        }
    }
    
    // Prüfe Stripe-Verbindung
    if (config('billing.stripe.secret')) {
        try {
            \Stripe\Stripe::setApiKey(config('billing.stripe.secret'));
            $balance = \Stripe\Balance::retrieve();
            success("Stripe-Verbindung: OK (Guthaben: " . ($balance->available[0]->amount / 100) . " EUR)");
        } catch (Exception $e) {
            error("Stripe-Verbindung: FEHLER - " . $e->getMessage());
            $criticalErrors[] = "Stripe-Verbindung fehlgeschlagen";
        }
    }
    
    $testResults['config'] = empty($criticalErrors);
    
} catch (Exception $e) {
    error("Konfigurationstest fehlgeschlagen: " . $e->getMessage());
    $testResults['config'] = false;
}

// ===========================================================================
// TEST 2: Datenbank-Struktur
// ===========================================================================
section("2. DATENBANK-STRUKTUR PRÜFEN");

try {
    $requiredTables = [
        'tenants' => ['balance_cents', 'tenant_type', 'parent_tenant_id', 'commission_rate'],
        'transactions' => ['tenant_id', 'type', 'amount_cents', 'balance_before_cents', 'balance_after_cents'],
        'balance_topups' => ['tenant_id', 'amount_cents', 'status', 'stripe_session_id'],
        'pricing_plans' => ['name', 'price_per_call_cents', 'price_per_minute_cents'],
        'commission_ledger' => ['reseller_tenant_id', 'customer_tenant_id', 'amount_cents'],
    ];
    
    $dbErrors = [];
    
    foreach ($requiredTables as $table => $columns) {
        if (DB::getSchemaBuilder()->hasTable($table)) {
            success("Tabelle '$table' existiert");
            
            // Prüfe Spalten
            foreach ($columns as $column) {
                if (!DB::getSchemaBuilder()->hasColumn($table, $column)) {
                    error("  Spalte '$column' fehlt in '$table'");
                    $dbErrors[] = "Fehlende Spalte: $table.$column";
                }
            }
        } else {
            error("Tabelle '$table' FEHLT!");
            $dbErrors[] = "Fehlende Tabelle: $table";
        }
    }
    
    if (empty($dbErrors)) {
        success("Alle Datenbank-Strukturen vorhanden");
        $testResults['database'] = true;
    } else {
        $criticalErrors = array_merge($criticalErrors, $dbErrors);
        $testResults['database'] = false;
    }
    
} catch (Exception $e) {
    error("Datenbanktest fehlgeschlagen: " . $e->getMessage());
    $testResults['database'] = false;
}

// ===========================================================================
// TEST 3: Models & Relationships
// ===========================================================================
section("3. MODELS & RELATIONSHIPS PRÜFEN");

try {
    // Erstelle Test-Tenant
    DB::beginTransaction();
    
    $testTenant = Tenant::create([
        'id' => 99999,
        'name' => 'Test Tenant Produktion',
        'slug' => 'test-tenant-prod-' . time(),
        'balance_cents' => 10000,
        'tenant_type' => 'direct_customer',
        'is_active' => true,
    ]);
    
    success("Test-Tenant erstellt: ID {$testTenant->id}");
    
    // Teste Guthaben-Methoden
    $balanceBefore = $testTenant->balance_cents;
    $testTenant->addCredit(5000, 'Test-Aufladung');
    
    if ($testTenant->balance_cents === $balanceBefore + 5000) {
        success("addCredit() funktioniert: +50€");
    } else {
        error("addCredit() fehlerhaft");
        $criticalErrors[] = "Tenant::addCredit() funktioniert nicht";
    }
    
    // Teste Transaktion
    $transaction = Transaction::where('tenant_id', $testTenant->id)
        ->where('type', 'topup')
        ->first();
    
    if ($transaction) {
        success("Transaktion erstellt: {$transaction->getFormattedAmount()}");
        
        if ($transaction->description === 'Test-Aufladung') {
            success("Transaktions-Beschreibung korrekt");
        } else {
            warning("Transaktions-Beschreibung nicht korrekt");
        }
    } else {
        error("Keine Transaktion erstellt");
        $criticalErrors[] = "Transaktionserstellung fehlgeschlagen";
    }
    
    // Teste Reseller-Struktur
    $reseller = Tenant::create([
        'id' => 99998,
        'name' => 'Test Reseller',
        'slug' => 'test-reseller-' . time(),
        'balance_cents' => 50000,
        'tenant_type' => 'reseller',
        'commission_rate' => 25.0,
        'is_active' => true,
    ]);
    
    $resellerCustomer = Tenant::create([
        'id' => 99997,
        'name' => 'Test Reseller Kunde',
        'slug' => 'test-reseller-kunde-' . time(),
        'balance_cents' => 5000,
        'tenant_type' => 'reseller_customer',
        'parent_tenant_id' => $reseller->id,
        'is_active' => true,
    ]);
    
    if ($resellerCustomer->parent_tenant_id === $reseller->id) {
        success("Reseller-Hierarchie funktioniert");
    } else {
        error("Reseller-Hierarchie fehlerhaft");
        $criticalErrors[] = "Parent-Tenant Beziehung funktioniert nicht";
    }
    
    DB::rollBack();
    success("Test-Daten zurückgerollt");
    
    $testResults['models'] = empty($criticalErrors);
    
} catch (Exception $e) {
    DB::rollBack();
    error("Model-Test fehlgeschlagen: " . $e->getMessage());
    $testResults['models'] = false;
}

// ===========================================================================
// TEST 4: Billing Chain Service
// ===========================================================================
section("4. BILLING CHAIN SERVICE PRÜFEN");

try {
    DB::beginTransaction();
    
    // Erstelle Test-Setup
    $platform = Tenant::create([
        'id' => 99996,
        'name' => 'Platform Test',
        'slug' => 'platform-test-' . time(),
        'balance_cents' => 0,
        'tenant_type' => 'platform',
        'is_active' => true,
    ]);
    
    $reseller = Tenant::create([
        'id' => 99995,
        'name' => 'Reseller Test',
        'slug' => 'reseller-test-' . time(),
        'balance_cents' => 10000,
        'tenant_type' => 'reseller',
        'commission_rate' => 25.0,
        'is_active' => true,
    ]);
    
    $customer = Tenant::create([
        'id' => 99994,
        'name' => 'Customer Test',
        'slug' => 'customer-test-' . time(),
        'balance_cents' => 1000,
        'tenant_type' => 'reseller_customer',
        'parent_tenant_id' => $reseller->id,
        'is_active' => true,
    ]);
    
    // Teste Billing Chain
    $billingService = app(BillingChainService::class);
    
    try {
        // 10 Minuten Anruf (400 Cent Kosten für Kunde)
        $result = $billingService->processBillingChain(
            $customer,
            'call_minutes',
            10,
            ['description' => 'Test-Anruf 10 Minuten']
        );
        
        if ($result['success']) {
            success("Billing Chain erfolgreich verarbeitet");
            
            // Prüfe Guthaben
            $customer->refresh();
            $reseller->refresh();
            
            $expectedCustomerBalance = 1000 - 400; // 10€ - 4€
            $expectedResellerBalance = 10000 - 300 + 100; // 100€ - 3€ + 1€ Provision
            
            if ($customer->balance_cents === $expectedCustomerBalance) {
                success("Kundenguthaben korrekt: " . $customer->getFormattedBalance());
            } else {
                error("Kundenguthaben falsch. Erwartet: {$expectedCustomerBalance}, Ist: {$customer->balance_cents}");
            }
            
            if ($reseller->balance_cents === $expectedResellerBalance) {
                success("Reseller-Guthaben korrekt: " . $reseller->getFormattedBalance());
            } else {
                error("Reseller-Guthaben falsch. Erwartet: {$expectedResellerBalance}, Ist: {$reseller->balance_cents}");
            }
            
            // Prüfe Transaktionen
            $transactions = Transaction::where('tenant_id', $customer->id)
                ->orWhere('tenant_id', $reseller->id)
                ->get();
            
            if ($transactions->count() >= 2) {
                success("Transaktionen erstellt: {$transactions->count()}");
            } else {
                error("Zu wenige Transaktionen erstellt");
            }
            
        } else {
            error("Billing Chain fehlgeschlagen: " . ($result['message'] ?? 'Unbekannter Fehler'));
            $criticalErrors[] = "BillingChainService funktioniert nicht";
        }
        
    } catch (Exception $e) {
        error("Billing Chain Exception: " . $e->getMessage());
        $criticalErrors[] = "BillingChainService wirft Exception";
    }
    
    DB::rollBack();
    success("Billing Chain Test-Daten zurückgerollt");
    
    $testResults['billing_chain'] = empty($criticalErrors);
    
} catch (Exception $e) {
    DB::rollBack();
    error("Billing Chain Test fehlgeschlagen: " . $e->getMessage());
    $testResults['billing_chain'] = false;
}

// ===========================================================================
// TEST 5: API Endpoints
// ===========================================================================
section("5. API ENDPOINTS PRÜFEN");

try {
    $routes = [
        'GET /billing' => 'billing.index',
        'GET /billing/transactions' => 'billing.transactions',
        'GET /billing/topup' => 'billing.topup',
        'POST /billing/checkout' => 'billing.checkout',
        'POST /billing/webhook' => 'billing.webhook',
        'GET /billing/success' => 'billing.success',
        'GET /billing/cancel' => 'billing.cancel',
    ];
    
    $routeErrors = [];
    
    foreach ($routes as $route => $name) {
        if (Route::has($name)) {
            success("Route '$route' registriert");
        } else {
            error("Route '$route' FEHLT!");
            $routeErrors[] = "Fehlende Route: $name";
        }
    }
    
    if (empty($routeErrors)) {
        $testResults['routes'] = true;
    } else {
        $warnings = array_merge($warnings, $routeErrors);
        $testResults['routes'] = false;
    }
    
} catch (Exception $e) {
    error("Route-Test fehlgeschlagen: " . $e->getMessage());
    $testResults['routes'] = false;
}

// ===========================================================================
// TEST 6: Performance
// ===========================================================================
section("6. PERFORMANCE PRÜFEN");

try {
    $startTime = microtime(true);
    
    // Teste Transaktions-Performance
    DB::beginTransaction();
    
    $perfTenant = Tenant::create([
        'id' => 99993,
        'name' => 'Performance Test',
        'slug' => 'perf-test-' . time(),
        'balance_cents' => 100000,
        'tenant_type' => 'direct_customer',
        'is_active' => true,
    ]);
    
    $transactionTimes = [];
    
    for ($i = 0; $i < 10; $i++) {
        $txStart = microtime(true);
        
        Transaction::create([
            'tenant_id' => $perfTenant->id,
            'type' => 'usage',
            'amount_cents' => -10,
            'balance_before_cents' => $perfTenant->balance_cents,
            'balance_after_cents' => $perfTenant->balance_cents - 10,
            'description' => "Performance Test $i",
        ]);
        
        $perfTenant->decrement('balance_cents', 10);
        
        $transactionTimes[] = (microtime(true) - $txStart) * 1000;
    }
    
    DB::rollBack();
    
    $avgTime = array_sum($transactionTimes) / count($transactionTimes);
    $maxTime = max($transactionTimes);
    
    if ($avgTime < 50) { // Unter 50ms
        success(sprintf("Durchschnittliche Transaktionszeit: %.2fms", $avgTime));
    } else {
        warning(sprintf("Langsame Transaktionen: %.2fms (Ziel: <50ms)", $avgTime));
        $warnings[] = "Transaktions-Performance langsam";
    }
    
    info(sprintf("Maximale Transaktionszeit: %.2fms", $maxTime));
    
    $testResults['performance'] = $avgTime < 100;
    
} catch (Exception $e) {
    DB::rollBack();
    error("Performance-Test fehlgeschlagen: " . $e->getMessage());
    $testResults['performance'] = false;
}

// ===========================================================================
// ZUSAMMENFASSUNG
// ===========================================================================
section("TEST-ZUSAMMENFASSUNG");

$totalTests = count($testResults);
$passedTests = count(array_filter($testResults));
$failedTests = $totalTests - $passedTests;

echo "\n";
info("Gesamt-Tests: $totalTests");
success("Bestanden: $passedTests");
if ($failedTests > 0) {
    error("Fehlgeschlagen: $failedTests");
}

// Zeige kritische Fehler
if (!empty($criticalErrors)) {
    echo "\n";
    error("KRITISCHE FEHLER:");
    foreach ($criticalErrors as $error) {
        echo "  • $error\n";
    }
}

// Zeige Warnungen
if (!empty($warnings)) {
    echo "\n";
    warning("WARNUNGEN:");
    foreach ($warnings as $warning) {
        echo "  • $warning\n";
    }
}

// Deployment-Empfehlung
echo "\n";
section("DEPLOYMENT-EMPFEHLUNG");

if (empty($criticalErrors)) {
    if (empty($warnings)) {
        success("✅ SYSTEM IST PRODUKTIONSBEREIT!");
        echo "\n";
        info("Empfohlene nächste Schritte:");
        echo "  1. Führen Sie das Backup-Script aus\n";
        echo "  2. Aktivieren Sie das Billing-System in .env\n";
        echo "  3. Konfigurieren Sie Stripe-Webhooks\n";
        echo "  4. Führen Sie eine Test-Zahlung durch\n";
        echo "  5. Aktivieren Sie Health-Check Monitoring\n";
    } else {
        warning("⚠️  SYSTEM IST BEREIT MIT WARNUNGEN");
        echo "\n";
        info("Bitte prüfen Sie die Warnungen vor dem Deployment.");
    }
} else {
    error("❌ SYSTEM IST NICHT PRODUKTIONSBEREIT!");
    echo "\n";
    error("Kritische Fehler müssen behoben werden vor dem Deployment.");
}

echo "\n";
info("Test abgeschlossen: " . date('Y-m-d H:i:s'));
echo "\n";

// Exit-Code basierend auf Ergebnis
exit(empty($criticalErrors) ? 0 : 1);
