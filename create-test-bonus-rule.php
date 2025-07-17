<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BillingBonusRule;

// Create a global bonus rule
$rule = BillingBonusRule::create([
    'company_id' => null, // Global rule
    'name' => '10% Bonus ab 100€',
    'description' => 'Erhalte 10% Bonus bei Aufladungen ab 100€',
    'min_amount' => 100.00,
    'max_amount' => null,
    'bonus_percentage' => 10.00,
    'max_bonus_amount' => null,
    'is_first_time_only' => false,
    'is_active' => true,
    'priority' => 100,
    'valid_from' => now(),
    'valid_until' => null,
]);

echo "Created bonus rule: {$rule->name}\n";

// Create another rule for larger amounts
$rule2 = BillingBonusRule::create([
    'company_id' => null,
    'name' => '15% Bonus ab 500€',
    'description' => 'Erhalte 15% Bonus bei Aufladungen ab 500€',
    'min_amount' => 500.00,
    'max_amount' => null,
    'bonus_percentage' => 15.00,
    'max_bonus_amount' => 200.00, // Max 200€ Bonus
    'is_first_time_only' => false,
    'is_active' => true,
    'priority' => 200,
    'valid_from' => now(),
    'valid_until' => null,
]);

echo "Created bonus rule: {$rule2->name}\n";

// Create first-time bonus
$rule3 = BillingBonusRule::create([
    'company_id' => null,
    'name' => 'Willkommensbonus 20%',
    'description' => 'Einmaliger Willkommensbonus von 20% bei der ersten Aufladung',
    'min_amount' => 50.00,
    'max_amount' => null,
    'bonus_percentage' => 20.00,
    'max_bonus_amount' => 50.00, // Max 50€ Bonus
    'is_first_time_only' => true,
    'is_active' => true,
    'priority' => 300,
    'valid_from' => now(),
    'valid_until' => null,
]);

echo "Created bonus rule: {$rule3->name}\n";

echo "\nAll bonus rules created successfully!\n";