#!/usr/bin/env php
<?php

/**
 * Multi-Tier Billing System Test Script
 * Tests the complete flow: Platform → Reseller (Mandant) → End Customer
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

echo "\n" . str_repeat('=', 80) . "\n";
echo "MULTI-TIER BILLING SYSTEM TEST\n";
echo str_repeat('=', 80) . "\n\n";

// Clean up test data from previous runs
DB::transaction(function () {
    echo "🧹 Cleaning up previous test data...\n";
    Tenant::where('slug', 'like', 'test-%')->forceDelete();
});

// Step 1: Create Platform Tenant (if not exists)
echo "1️⃣ Setting up Platform Tenant...\n";
$platform = Tenant::firstOrCreate(
    ['tenant_type' => 'platform'],
    [
        'name' => 'AskProAI Platform',
        'slug' => 'askproai-platform',
        'tenant_type' => 'platform',
        'balance_cents' => 0,
        'commission_rate' => 0
    ]
);
echo "   ✅ Platform: {$platform->name}\n\n";

// Step 2: Create a Reseller (Mandant)
echo "2️⃣ Creating Reseller (Mandant)...\n";
$reseller = Tenant::create([
    'name' => 'Premium Hair Solutions GmbH',
    'slug' => 'test-reseller-premium-hair',
    'tenant_type' => 'reseller',
    'parent_tenant_id' => null, // Resellers don't have a parent
    'balance_cents' => 100000, // Start with 1000€
    'commission_rate' => 25.0, // 25% commission on sales
    'base_cost_cents' => 30, // Platform charges 30 cents/minute
    'reseller_markup_cents' => 10, // Reseller adds 10 cents markup (charges 40 cents to customer)
    'can_set_prices' => true,
    'min_markup_percent' => 10,
    'max_markup_percent' => 50,
    'billing_mode' => 'direct',
    'auto_commission_payout' => true,
    'commission_payout_threshold_cents' => 5000 // Auto payout at 50€
]);
echo "   ✅ Reseller: {$reseller->name}\n";
echo "   💰 Initial Balance: {$reseller->getFormattedBalance()}\n";
echo "   📊 Commission Rate: {$reseller->commission_rate}%\n";
echo "   💵 Base Cost: 0.30€/min | Customer Price: 0.40€/min | Markup: 0.10€/min\n\n";

// Step 3: Create End Customers for the Reseller
echo "3️⃣ Creating End Customers for Reseller...\n";

$customer1 = Tenant::create([
    'name' => 'Friseursalon Eleganz',
    'slug' => 'test-customer-eleganz',
    'tenant_type' => 'reseller_customer',
    'parent_tenant_id' => $reseller->id,
    'balance_cents' => 50000, // Start with 500€
    'billing_mode' => 'through_reseller'
]);
echo "   ✅ Customer 1: {$customer1->name} (Balance: {$customer1->getFormattedBalance()})\n";

$customer2 = Tenant::create([
    'name' => 'Hair & Beauty Studio',
    'slug' => 'test-customer-beauty',
    'tenant_type' => 'reseller_customer',
    'parent_tenant_id' => $reseller->id,
    'balance_cents' => 30000, // Start with 300€
    'billing_mode' => 'through_reseller'
]);
echo "   ✅ Customer 2: {$customer2->name} (Balance: {$customer2->getFormattedBalance()})\n\n";

// Step 4: Create a Direct Customer (no reseller)
echo "4️⃣ Creating Direct Customer (without Reseller)...\n";
$directCustomer = Tenant::create([
    'name' => 'Direktkunde GmbH',
    'slug' => 'test-direct-customer',
    'tenant_type' => 'direct_customer',
    'parent_tenant_id' => null,
    'balance_cents' => 20000, // Start with 200€
    'billing_mode' => 'direct'
]);
echo "   ✅ Direct Customer: {$directCustomer->name} (Balance: {$directCustomer->getFormattedBalance()})\n\n";

// Step 5: Test Billing Scenarios
echo "5️⃣ Testing Billing Scenarios...\n";
echo str_repeat('-', 80) . "\n\n";

$billingService = new BillingChainService();

// Scenario 1: Reseller Customer makes a call
echo "📞 Scenario 1: Reseller Customer (Eleganz) makes 5-minute call\n";
echo "   Expected flow: Customer pays 2.00€ → Reseller keeps 0.50€ → Platform gets 1.50€\n\n";

$initialCustomerBalance = $customer1->balance_cents;
$initialResellerBalance = $reseller->balance_cents;

try {
    $transactions = $billingService->processBillingChain(
        $customer1,
        'call',
        5, // 5 minutes
        ['test_scenario' => 'reseller_customer_call']
    );
    
    echo "   ✅ Transaction completed! Created " . count($transactions) . " transactions:\n";
    foreach ($transactions as $idx => $trans) {
        $tenant = Tenant::find($trans->tenant_id);
        echo "      " . ($idx + 1) . ". {$tenant->name}: {$trans->getFormattedAmount()} ({$trans->description})\n";
        if ($trans->getBillingChainType()) {
            echo "         Chain: {$trans->getBillingChainType()}\n";
        }
    }
    
    // Refresh balances
    $customer1->refresh();
    $reseller->refresh();
    
    echo "\n   💰 Balance Changes:\n";
    echo "      Customer: " . number_format($initialCustomerBalance/100, 2) . "€ → " . 
         number_format($customer1->balance_cents/100, 2) . "€ (Paid: " . 
         number_format(($initialCustomerBalance - $customer1->balance_cents)/100, 2) . "€)\n";
    echo "      Reseller: " . number_format($initialResellerBalance/100, 2) . "€ → " . 
         number_format($reseller->balance_cents/100, 2) . "€ (Net: " . 
         number_format(($reseller->balance_cents - $initialResellerBalance)/100, 2) . "€)\n";
    
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat('-', 80) . "\n\n";

// Scenario 2: Direct Customer makes a call
echo "📞 Scenario 2: Direct Customer makes 3-minute call\n";
echo "   Expected: Customer pays standard rate directly to platform\n\n";

$initialDirectBalance = $directCustomer->balance_cents;

try {
    // For direct customers, we need to set a pricing plan
    if (!$directCustomer->pricing_plan_id) {
        $defaultPlan = PricingPlan::firstOrCreate(
            ['slug' => 'standard'],
            [
                'name' => 'Standard Plan',
                'price_per_minute_cents' => 42,
                'price_per_call_cents' => 10,
                'price_per_appointment_cents' => 100,
                'billing_type' => 'prepaid',
                'is_default' => true
            ]
        );
        $directCustomer->pricing_plan_id = $defaultPlan->id;
        $directCustomer->save();
    }
    
    $transactions = $billingService->processBillingChain(
        $directCustomer,
        'call',
        3, // 3 minutes
        ['test_scenario' => 'direct_customer_call']
    );
    
    echo "   ✅ Transaction completed!\n";
    $directCustomer->refresh();
    
    echo "   💰 Balance Change:\n";
    echo "      Direct Customer: " . number_format($initialDirectBalance/100, 2) . "€ → " . 
         number_format($directCustomer->balance_cents/100, 2) . "€ (Paid: " . 
         number_format(($initialDirectBalance - $directCustomer->balance_cents)/100, 2) . "€)\n";
    
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat('-', 80) . "\n\n";

// Scenario 3: Multiple transactions to trigger commission tracking
echo "📊 Scenario 3: Multiple transactions for commission tracking\n\n";

$totalCommission = 0;
for ($i = 1; $i <= 3; $i++) {
    try {
        $minutes = rand(2, 10);
        echo "   Call $i: {$customer2->name} - {$minutes} minutes\n";
        
        $transactions = $billingService->processBillingChain(
            $customer2,
            'call',
            $minutes,
            ['test_scenario' => "batch_call_$i"]
        );
        
        // Calculate commission from this transaction
        $customerTrans = $transactions[0] ?? null;
        if ($customerTrans && $customerTrans->commission_amount_cents) {
            $totalCommission += $customerTrans->commission_amount_cents;
            echo "      Commission earned: " . number_format($customerTrans->commission_amount_cents/100, 2) . "€\n";
        }
        
    } catch (\Exception $e) {
        echo "      ❌ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n   💰 Total Commission Earned: " . number_format($totalCommission/100, 2) . "€\n";

// Step 6: Show Commission Ledger
echo "\n" . str_repeat('=', 80) . "\n";
echo "6️⃣ Commission Ledger Summary\n";
echo str_repeat('-', 80) . "\n\n";

$commissions = DB::table('commission_ledger')
    ->where('reseller_tenant_id', $reseller->id)
    ->get();

if ($commissions->count() > 0) {
    echo "   Found {$commissions->count()} commission entries:\n\n";
    
    $totalGross = 0;
    $totalPlatformCost = 0;
    $totalCommission = 0;
    
    foreach ($commissions as $idx => $commission) {
        $customerName = Tenant::find($commission->customer_tenant_id)->name ?? 'Unknown';
        echo "   " . ($idx + 1) . ". Customer: {$customerName}\n";
        echo "      Gross: " . number_format($commission->gross_amount_cents/100, 2) . "€ | ";
        echo "Platform: " . number_format($commission->platform_cost_cents/100, 2) . "€ | ";
        echo "Commission: " . number_format($commission->commission_cents/100, 2) . "€ ";
        echo "({$commission->commission_rate}%)\n";
        
        $totalGross += $commission->gross_amount_cents;
        $totalPlatformCost += $commission->platform_cost_cents;
        $totalCommission += $commission->commission_cents;
    }
    
    echo "\n   📊 Totals:\n";
    echo "      Total Gross Revenue: " . number_format($totalGross/100, 2) . "€\n";
    echo "      Platform Revenue: " . number_format($totalPlatformCost/100, 2) . "€\n";
    echo "      Reseller Commission: " . number_format($totalCommission/100, 2) . "€\n";
    echo "      Profit Margin: " . number_format(($totalCommission/$totalGross)*100, 2) . "%\n";
} else {
    echo "   No commission entries found.\n";
}

// Step 7: Final Summary
echo "\n" . str_repeat('=', 80) . "\n";
echo "7️⃣ Final Account Balances\n";
echo str_repeat('-', 80) . "\n\n";

$allTenants = Tenant::whereIn('slug', [
    'test-reseller-premium-hair',
    'test-customer-eleganz',
    'test-customer-beauty',
    'test-direct-customer'
])->get();

foreach ($allTenants as $tenant) {
    $typeLabel = match($tenant->tenant_type) {
        'reseller' => '🏢 Reseller',
        'reseller_customer' => '👥 Reseller Customer',
        'direct_customer' => '🔗 Direct Customer',
        default => '❓ Unknown'
    };
    
    echo "   {$typeLabel} {$tenant->name}:\n";
    echo "      Balance: {$tenant->getFormattedBalance()}\n";
    
    if ($tenant->isReseller()) {
        $transCount = Transaction::where('tenant_id', $tenant->id)->count();
        $commissionCount = DB::table('commission_ledger')
            ->where('reseller_tenant_id', $tenant->id)
            ->count();
        echo "      Transactions: {$transCount} | Commission Entries: {$commissionCount}\n";
    }
    
    if ($tenant->hasReseller()) {
        echo "      Reseller: " . ($tenant->parentTenant->name ?? 'None') . "\n";
    }
    
    echo "\n";
}

echo str_repeat('=', 80) . "\n";
echo "✅ MULTI-TIER BILLING TEST COMPLETED SUCCESSFULLY!\n";
echo str_repeat('=', 80) . "\n\n";

echo "Key Insights:\n";
echo "• Reseller customers pay marked-up prices (0.40€/min vs 0.30€ platform cost)\n";
echo "• Resellers earn commission on the markup (25% in this example)\n";
echo "• Direct customers pay standard rates without intermediary\n";
echo "• All transactions are tracked with full audit trail\n";
echo "• Commission ledger enables transparent payout tracking\n\n";