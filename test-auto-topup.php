<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Services\AutoTopupService;
use App\Services\PrepaidBillingService;

// Get first company
$company = Company::first();
if (!$company) {
    echo "No company found.\n";
    exit;
}

echo "Testing Auto-Topup for Company: {$company->name}\n";
echo "===================================\n";

// Services
$billingService = app(PrepaidBillingService::class);
$autoTopupService = app(AutoTopupService::class);

// Get or create balance
$balance = $billingService->getOrCreateBalance($company);

echo "\nCurrent Balance:\n";
echo "- Normal Balance: €" . number_format($balance->balance, 2) . "\n";
echo "- Bonus Balance: €" . number_format($balance->bonus_balance, 2) . "\n";
echo "- Total: €" . number_format($balance->getTotalBalance(), 2) . "\n";

echo "\nAuto-Topup Settings:\n";
echo "- Enabled: " . ($balance->auto_topup_enabled ? 'Yes' : 'No') . "\n";
echo "- Threshold: €" . number_format($balance->auto_topup_threshold, 2) . "\n";
echo "- Amount: €" . number_format($balance->auto_topup_amount, 2) . "\n";
echo "- Payment Method: " . ($balance->auto_topup_payment_method_id ?: 'None') . "\n";

// Check if auto-topup would trigger
echo "\n\nSimulating Auto-Topup Check:\n";
echo "----------------------------\n";

// Temporarily set balance low to test
$originalBalance = $balance->balance;
$balance->balance = 15.00; // Below threshold
$balance->save();

echo "Simulated balance: €15.00\n";

// Check auto-topup
$result = $autoTopupService->checkAndExecuteAutoTopup($company);

if ($result) {
    echo "\nAuto-Topup would be triggered!\n";
    echo "- Status: " . $result['status'] . "\n";
    if (isset($result['message'])) {
        echo "- Message: " . $result['message'] . "\n";
    }
} else {
    echo "\nAuto-Topup would NOT be triggered.\n";
}

// Restore original balance
$balance->balance = $originalBalance;
$balance->save();

// Show bonus rules
echo "\n\nApplicable Bonus Rules:\n";
echo "-----------------------\n";
$bonusRules = $billingService->getApplicableBonusRules($company);
foreach ($bonusRules as $rule) {
    if (is_object($rule)) {
        echo "- {$rule->name}: {$rule->description}\n";
        echo "  Min: €" . number_format($rule->min_amount, 2);
        if ($rule->max_amount) {
            echo " - Max: €" . number_format($rule->max_amount, 2);
        }
        echo "\n  Bonus: {$rule->bonus_percentage}%";
        if ($rule->max_bonus_amount) {
            echo " (max €" . number_format($rule->max_bonus_amount, 2) . ")";
        }
    } else {
        // Handle array format
        echo "- {$rule['name']}: {$rule['description']}\n";
        echo "  Min: €" . number_format($rule['min_amount'], 2);
        if ($rule['max_amount']) {
            echo " - Max: €" . number_format($rule['max_amount'], 2);
        }
        echo "\n  Bonus: {$rule['bonus_percentage']}%";
        if ($rule['max_bonus_amount']) {
            echo " (max €" . number_format($rule['max_bonus_amount'], 2) . ")";
        }
    }
    echo "\n\n";
}

// Test bonus calculation
$testAmounts = [50, 100, 200, 500, 1000];
echo "Bonus Calculations:\n";
echo "-------------------\n";
foreach ($testAmounts as $amount) {
    $bonusCalc = $billingService->calculateBonus($amount, $company);
    $bonus = $bonusCalc['bonus_amount'];
    $total = $amount + $bonus;
    
    echo "€" . number_format($amount, 2) . " -> ";
    if ($bonus > 0) {
        echo "+" . number_format($bonus, 2) . " bonus = €" . number_format($total, 2);
        if ($bonusCalc['rule']) {
            echo " ({$bonusCalc['rule']->name})";
        }
    } else {
        echo "no bonus";
    }
    echo "\n";
}

echo "\nTest completed.\n";