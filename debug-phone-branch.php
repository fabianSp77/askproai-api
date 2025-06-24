#!/usr/bin/env php
<?php
/**
 * Debug phone number and branch setup
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Services\PhoneNumberResolver;

echo "\n========================================\n";
echo "DEBUG PHONE & BRANCH SETUP\n";
echo "========================================\n";

// Check branches
echo "\n[BRANCHES]\n";
$branches = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('phone_number', '+49 30 837 93 369')
    ->get();

echo "Branches with phone +49 30 837 93 369: " . $branches->count() . "\n";
foreach ($branches as $branch) {
    echo "- {$branch->name} (ID: {$branch->id}, Company: {$branch->company_id}, Active: " . ($branch->is_active ? 'Yes' : 'No') . ")\n";
}

// Check phone numbers table
echo "\n[PHONE NUMBERS]\n";
$phoneNumbers = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('number', '+49 30 837 93 369')
    ->get();

echo "Phone number records: " . $phoneNumbers->count() . "\n";
foreach ($phoneNumbers as $phone) {
    echo "- Number: {$phone->number} (Branch: {$phone->branch_id}, Company: {$phone->company_id}, Active: " . ($phone->is_active ? 'Yes' : 'No') . ")\n";
}

// Test PhoneNumberResolver
echo "\n[PHONE RESOLVER TEST]\n";
$resolver = app(PhoneNumberResolver::class);

$testNumber = '+49 30 837 93 369';
echo "Testing resolveFromPhone('$testNumber')...\n";

try {
    $result = $resolver->resolveFromPhone($testNumber);
    echo "Result:\n";
    print_r($result);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// Test simple resolve
echo "\nTesting resolve('$testNumber')...\n";
try {
    $result = $resolver->resolve($testNumber);
    echo "Result:\n";
    print_r($result);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n========================================\n\n";