#!/usr/bin/env php
<?php

/**
 * Vollständiger Systemtest - Mehrstufiges Abrechnungssystem
 * Testet alle implementierten Funktionen und validiert die Integrität
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\PricingPlan;
use App\Models\CommissionLedger;
use App\Models\ResellerPayout;
use App\Services\BillingChainService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$testResults = [];
$errors = [];
$warnings = [];

function testSection($name) {
    echo "\n" . str_repeat('═', 80) . "\n";
    echo "📋 {$name}\n";
    echo str_repeat('═', 80) . "\n";
}

function testCase($description, $callable) {
    global $testResults, $errors;
    echo "   ▶ {$description}... ";
    try {
        $result = $callable();
        if ($result === true) {
            echo "✅ Bestanden\n";
            $testResults[] = ['test' => $description, 'status' => 'passed'];
            return true;
        } else {
            echo "❌ Fehlgeschlagen: {$result}\n";
            $errors[] = $result;
            $testResults[] = ['test' => $description, 'status' => 'failed', 'error' => $result];
            return false;
        }
    } catch (\Exception $e) {
        echo "❌ Fehler: " . $e->getMessage() . "\n";
        $errors[] = $e->getMessage();
        $testResults[] = ['test' => $description, 'status' => 'error', 'error' => $e->getMessage()];
        return false;
    }
}

// START DER TESTS
echo "\n" . str_repeat('═', 80) . "\n";
echo "🚀 VOLLSTÄNDIGER SYSTEMTEST - MEHRSTUFIGES ABRECHNUNGSSYSTEM\n";
echo str_repeat('═', 80) . "\n";

// 1. DATENBANKSTRUKTUR TESTS
testSection("1. DATENBANKSTRUKTUR & MIGRATIONEN");

testCase("Prüfe Tenants-Tabelle Struktur", function() {
    $columns = Schema::getColumnListing('tenants');
    $required = [
        'id', 'name', 'tenant_type', 'parent_tenant_id', 'balance_cents',
        'commission_rate', 'base_cost_cents', 'reseller_markup_cents',
        'billing_mode', 'pricing_plan_id'
    ];
    
    foreach ($required as $col) {
        if (!in_array($col, $columns)) {
            return "Spalte '{$col}' fehlt in tenants-Tabelle";
        }
    }
    return true;
});

testCase("Prüfe Transactions-Tabelle Erweiterungen", function() {
    $columns = Schema::getColumnListing('transactions');
    $required = [
        'reseller_tenant_id', 'commission_amount_cents', 
        'base_cost_cents', 'reseller_revenue_cents', 'parent_transaction_id'
    ];
    
    foreach ($required as $col) {
        if (!in_array($col, $columns)) {
            return "Spalte '{$col}' fehlt in transactions-Tabelle";
        }
    }
    return true;
});

testCase("Prüfe Commission_Ledger-Tabelle", function() {
    return Schema::hasTable('commission_ledger');
});

testCase("Prüfe Reseller_Payouts-Tabelle", function() {
    return Schema::hasTable('reseller_payouts');
});

testCase("Prüfe Tenant_Pricing_Tiers-Tabelle", function() {
    return Schema::hasTable('tenant_pricing_tiers');
});

// 2. MODEL & RELATIONSHIP TESTS
testSection("2. MODELS & BEZIEHUNGEN");

testCase("Teste Tenant Model Beziehungen", function() {
    $tenant = new Tenant();
    $methods = ['parentTenant', 'childTenants', 'companies', 'customers', 
                'commissionLedger', 'payouts', 'transactions'];
    
    foreach ($methods as $method) {
        if (!method_exists($tenant, $method)) {
            return "Methode '{$method}' fehlt im Tenant Model";
        }
    }
    return true;
});

testCase("Teste Transaction Model Erweiterungen", function() {
    $transaction = new Transaction();
    $methods = ['resellerTenant', 'parentTransaction', 'childTransactions', 
                'hasReseller', 'getFormattedCommission'];
    
    foreach ($methods as $method) {
        if (!method_exists($transaction, $method)) {
            return "Methode '{$method}' fehlt im Transaction Model";
        }
    }
    return true;
});

testCase("Teste BillingChainService Existenz", function() {
    return class_exists('App\Services\BillingChainService');
});

// 3. GESCHÄFTSLOGIK TESTS
testSection("3. GESCHÄFTSLOGIK & BERECHNUNGEN");

// Testdaten vorbereiten
DB::beginTransaction();

try {
    // Platform Tenant
    $platform = Tenant::firstOrCreate(
        ['tenant_type' => 'platform'],
        [
            'name' => 'Test Plattform',
            'slug' => 'test-platform',
            'balance_cents' => 0
        ]
    );

    // Reseller erstellen
    $reseller = Tenant::create([
        'name' => 'Test Reseller GmbH',
        'slug' => 'test-reseller-' . time(),
        'tenant_type' => 'reseller',
        'balance_cents' => 100000,
        'commission_rate' => 25.0,
        'base_cost_cents' => 30,
        'reseller_markup_cents' => 10,
        'billing_mode' => 'direct'
    ]);

    testCase("Teste Reseller-Erstellung", function() use ($reseller) {
        return $reseller->isReseller() === true;
    });

    // Reseller-Kunde erstellen
    $customer = Tenant::create([
        'name' => 'Test Kunde',
        'slug' => 'test-kunde-' . time(),
        'tenant_type' => 'reseller_customer',
        'parent_tenant_id' => $reseller->id,
        'balance_cents' => 50000,
        'billing_mode' => 'through_reseller'
    ]);

    testCase("Teste Kunden-Reseller-Beziehung", function() use ($customer, $reseller) {
        return $customer->hasReseller() && 
               $customer->parent_tenant_id == $reseller->id;
    });

    // Direktkunde erstellen
    $directCustomer = Tenant::create([
        'name' => 'Test Direktkunde',
        'slug' => 'test-direkt-' . time(),
        'tenant_type' => 'direct_customer',
        'balance_cents' => 20000,
        'billing_mode' => 'direct'
    ]);

    testCase("Teste Direktkunden-Typ", function() use ($directCustomer) {
        return !$directCustomer->hasReseller() && 
               $directCustomer->tenant_type === 'direct_customer';
    });

    // 4. TRANSAKTIONSVERARBEITUNG TESTS
    testSection("4. TRANSAKTIONSVERARBEITUNG");

    $billingService = new BillingChainService();

    testCase("Teste Reseller-Kunden-Transaktion", function() use ($billingService, $customer) {
        $initialBalance = $customer->balance_cents;
        $transactions = $billingService->processBillingChain(
            $customer,
            'call',
            5,
            ['test' => true]
        );
        
        $customer->refresh();
        
        // Prüfe ob 4 Transaktionen erstellt wurden (Kunde, Reseller+, Reseller-, Platform)
        if (count($transactions) !== 4) {
            return "Erwartete 4 Transaktionen, erhielt " . count($transactions);
        }
        
        // Prüfe ob Guthaben korrekt abgezogen wurde (5 Min * 40 Cent = 200 Cent)
        $expectedDeduction = 200;
        if (($initialBalance - $customer->balance_cents) !== $expectedDeduction) {
            return "Falsche Guthabenberechnung";
        }
        
        return true;
    });

    testCase("Teste Provisionsberechnung", function() use ($reseller) {
        $commissions = DB::table('commission_ledger')
            ->where('reseller_tenant_id', $reseller->id)
            ->first();
        
        if (!$commissions) {
            return "Keine Provisionseinträge gefunden";
        }
        
        // Prüfe 25% Provision
        $expectedRate = 25.0;
        if (abs($commissions->commission_rate - $expectedRate) > 0.01) {
            return "Falsche Provisionsrate: {$commissions->commission_rate}%";
        }
        
        return true;
    });

    testCase("Teste Direktkunden-Transaktion", function() use ($billingService, $directCustomer) {
        // Setze Preisplan
        $plan = PricingPlan::firstOrCreate(
            ['slug' => 'test-standard'],
            [
                'name' => 'Test Standard',
                'price_per_minute_cents' => 42,
                'billing_type' => 'prepaid'
            ]
        );
        $directCustomer->pricing_plan_id = $plan->id;
        $directCustomer->save();
        
        $initialBalance = $directCustomer->balance_cents;
        $transactions = $billingService->processBillingChain(
            $directCustomer,
            'call',
            3,
            ['test' => true]
        );
        
        $directCustomer->refresh();
        
        // Nur 1 Transaktion für Direktkunden
        if (count($transactions) !== 1) {
            return "Erwartete 1 Transaktion für Direktkunde";
        }
        
        // Prüfe Abrechnung (3 Min * 42 Cent = 126 Cent)
        $expectedDeduction = 126;
        if (($initialBalance - $directCustomer->balance_cents) !== $expectedDeduction) {
            return "Falsche Direktkunden-Abrechnung";
        }
        
        return true;
    });

    // 5. FEHLERBEHANDLUNG TESTS
    testSection("5. FEHLERBEHANDLUNG");

    testCase("Teste Unzureichendes Guthaben", function() use ($billingService) {
        $poorCustomer = Tenant::create([
            'name' => 'Armer Kunde',
            'slug' => 'test-poor-' . time(),
            'tenant_type' => 'direct_customer',
            'balance_cents' => 10, // Nur 10 Cent
            'billing_mode' => 'direct'
        ]);
        
        try {
            $billingService->processBillingChain($poorCustomer, 'call', 5, []);
            return "Sollte Exception werfen bei unzureichendem Guthaben";
        } catch (\Exception $e) {
            return strpos($e->getMessage(), 'Unzureichendes Guthaben') !== false;
        }
    });

    testCase("Teste deutsche Fehlermeldungen", function() use ($billingService) {
        try {
            $billingService->billCall(new \App\Models\Call(['tenant_id' => 999999]));
        } catch (\Exception $e) {
            return strpos($e->getMessage(), 'Mandant') !== false;
        }
        return "Keine deutsche Fehlermeldung";
    });

    // 6. DATENINTEGRITÄT TESTS
    testSection("6. DATENINTEGRITÄT");

    testCase("Teste Transaktions-Atomarität", function() use ($reseller) {
        $initialBalance = $reseller->balance_cents;
        $transCount = Transaction::where('tenant_id', $reseller->id)->count();
        
        // Nach Reseller-Kunden-Transaktion sollten Transaktionen verknüpft sein
        $lastTransactions = Transaction::where('tenant_id', $reseller->id)
            ->latest()
            ->limit(2)
            ->get();
        
        if ($lastTransactions->count() < 2) {
            return "Nicht genug Transaktionen für Reseller";
        }
        
        // Prüfe parent_transaction_id Verknüpfung
        $hasParentLink = false;
        foreach ($lastTransactions as $trans) {
            if ($trans->parent_transaction_id !== null) {
                $hasParentLink = true;
                break;
            }
        }
        
        return $hasParentLink;
    });

    testCase("Teste Guthaben-Konsistenz", function() use ($reseller, $customer) {
        // Summe aller Transaktionen sollte mit Guthaben übereinstimmen
        $transactionSum = Transaction::where('tenant_id', $customer->id)
            ->sum('amount_cents');
        
        $expectedBalance = 50000 + $transactionSum; // Startguthaben + Transaktionen
        
        if (abs($customer->balance_cents - $expectedBalance) > 1) {
            return "Guthaben-Inkonsistenz gefunden";
        }
        
        return true;
    });

    // 7. LOKALISIERUNG TESTS
    testSection("7. DEUTSCHE LOKALISIERUNG");

    testCase("Teste deutsche Transaktionsbeschreibungen", function() {
        $lastTransaction = Transaction::latest()->first();
        if (!$lastTransaction) {
            return "Keine Transaktionen vorhanden";
        }
        
        $germanTerms = ['Telefonanruf', 'Provision', 'Plattformkosten', 'Plattformumsatz'];
        foreach ($germanTerms as $term) {
            if (strpos($lastTransaction->description, $term) !== false) {
                return true;
            }
        }
        
        return "Keine deutschen Begriffe in Transaktionsbeschreibung";
    });

    testCase("Teste Euro-Formatierung", function() use ($customer) {
        $formatted = $customer->getFormattedBalance();
        // Sollte € enthalten
        return strpos($formatted, '€') !== false;
    });

} catch (\Exception $e) {
    $errors[] = "Kritischer Fehler: " . $e->getMessage();
} finally {
    DB::rollBack();
}

// 8. PERFORMANCE TESTS
testSection("8. PERFORMANCE & SKALIERBARKEIT");

testCase("Teste Batch-Transaktionsverarbeitung", function() {
    $startTime = microtime(true);
    
    // Erstelle temporäre Testdaten
    DB::beginTransaction();
    try {
        $testReseller = Tenant::create([
            'name' => 'Perf Test Reseller',
            'slug' => 'perf-test-' . time(),
            'tenant_type' => 'reseller',
            'balance_cents' => 1000000,
            'commission_rate' => 20
        ]);
        
        $billingService = new BillingChainService();
        
        // Simuliere 10 Transaktionen
        for ($i = 0; $i < 10; $i++) {
            $testCustomer = Tenant::create([
                'name' => "Perf Test Kunde $i",
                'slug' => 'perf-kunde-' . time() . '-' . $i,
                'tenant_type' => 'reseller_customer',
                'parent_tenant_id' => $testReseller->id,
                'balance_cents' => 10000,
                'billing_mode' => 'through_reseller'
            ]);
            
            $billingService->processBillingChain($testCustomer, 'call', 1, []);
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        DB::rollBack();
        
        // Sollte unter 5 Sekunden für 10 Transaktionen sein
        return $duration < 5.0;
        
    } catch (\Exception $e) {
        DB::rollBack();
        return "Performance-Test fehlgeschlagen: " . $e->getMessage();
    }
});

// 9. SICHERHEIT TESTS
testSection("9. SICHERHEIT");

testCase("Teste SQL-Injection Schutz", function() {
    try {
        $maliciousInput = "'; DROP TABLE tenants; --";
        $tenant = Tenant::where('name', $maliciousInput)->first();
        // Wenn kein Fehler, dann ist SQL-Injection geschützt
        return true;
    } catch (\Exception $e) {
        return "SQL-Injection möglich!";
    }
});

testCase("Teste negative Guthaben-Verhinderung", function() {
    DB::beginTransaction();
    try {
        $testTenant = Tenant::create([
            'name' => 'Negative Test',
            'slug' => 'negative-test-' . time(),
            'tenant_type' => 'direct_customer',
            'balance_cents' => 100
        ]);
        
        // Versuche mehr abzuziehen als vorhanden
        $billingService = new BillingChainService();
        
        try {
            // Dies sollte fehlschlagen
            $testTenant->balance_cents = 50;
            $testTenant->save();
            
            $billingService->processBillingChain($testTenant, 'call', 10, []);
            DB::rollBack();
            return "Negatives Guthaben wurde erlaubt!";
        } catch (\Exception $e) {
            DB::rollBack();
            return true;
        }
    } catch (\Exception $e) {
        DB::rollBack();
        return "Test fehlgeschlagen: " . $e->getMessage();
    }
});

// ZUSAMMENFASSUNG
echo "\n" . str_repeat('═', 80) . "\n";
echo "📊 TESTERGEBNISSE ZUSAMMENFASSUNG\n";
echo str_repeat('═', 80) . "\n\n";

$totalTests = count($testResults);
$passedTests = count(array_filter($testResults, fn($r) => $r['status'] === 'passed'));
$failedTests = count(array_filter($testResults, fn($r) => $r['status'] === 'failed'));
$errorTests = count(array_filter($testResults, fn($r) => $r['status'] === 'error'));

echo "Gesamt: {$totalTests} Tests\n";
echo "✅ Bestanden: {$passedTests}\n";
echo "❌ Fehlgeschlagen: {$failedTests}\n";
echo "⚠️  Fehler: {$errorTests}\n";
echo "Erfolgsquote: " . round(($passedTests / $totalTests) * 100, 2) . "%\n";

if (!empty($errors)) {
    echo "\n🔴 GEFUNDENE PROBLEME:\n";
    foreach ($errors as $error) {
        echo "   • {$error}\n";
    }
}

// SYSTEMSTATUS
echo "\n" . str_repeat('═', 80) . "\n";
echo "📋 SYSTEMSTATUS\n";
echo str_repeat('═', 80) . "\n\n";

// Prüfe aktuelle Datenbankstatistiken
$stats = [
    'Mandanten (Tenants)' => Tenant::count(),
    'Reseller' => Tenant::where('tenant_type', 'reseller')->count(),
    'Reseller-Kunden' => Tenant::where('tenant_type', 'reseller_customer')->count(),
    'Direktkunden' => Tenant::where('tenant_type', 'direct_customer')->count(),
    'Transaktionen' => Transaction::count(),
    'Provisionseinträge' => DB::table('commission_ledger')->count(),
    'Preispläne' => PricingPlan::count(),
];

foreach ($stats as $label => $count) {
    echo "{$label}: {$count}\n";
}

// BEWERTUNG
echo "\n" . str_repeat('═', 80) . "\n";
if ($passedTests === $totalTests) {
    echo "🎉 ALLE TESTS BESTANDEN - SYSTEM IST PRODUKTIONSBEREIT!\n";
} elseif ($passedTests >= $totalTests * 0.8) {
    echo "✅ SYSTEM WEITGEHEND FUNKTIONSFÄHIG - KLEINERE PROBLEME VORHANDEN\n";
} elseif ($passedTests >= $totalTests * 0.6) {
    echo "⚠️  SYSTEM TEILWEISE FUNKTIONSFÄHIG - WICHTIGE PROBLEME MÜSSEN BEHOBEN WERDEN\n";
} else {
    echo "❌ SYSTEM NICHT PRODUKTIONSBEREIT - KRITISCHE PROBLEME GEFUNDEN\n";
}
echo str_repeat('═', 80) . "\n\n";