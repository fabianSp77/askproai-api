<?php

/**
 * Analysiere alle Phone Numbers
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PhoneNumber;
use App\Scopes\TenantScope;

echo "=== Phone Number Analyse ===\n\n";

// Alle Phone Numbers mit der neuen Nummer
$phoneNumbers = PhoneNumber::withoutGlobalScope(TenantScope::class)
    ->where('number', '+493033081738')
    ->get();

echo "Phone Numbers mit +493033081738:\n";
foreach ($phoneNumbers as $phone) {
    echo "  - ID: {$phone->id}\n";
    echo "    Company ID: {$phone->company_id}\n";
    echo "    Branch ID: {$phone->branch_id}\n";
    echo "    Active: " . ($phone->is_active ? 'JA' : 'NEIN') . "\n";
    echo "    Agent ID: {$phone->retell_agent_id}\n\n";
}

// Krückeberg Phone Numbers
echo "\nPhone Numbers für Krückeberg (Company ID 1):\n";
$krueckebergPhones = PhoneNumber::withoutGlobalScope(TenantScope::class)
    ->where('company_id', 1)
    ->get();

foreach ($krueckebergPhones as $phone) {
    echo "  - Number: {$phone->number}\n";
    echo "    ID: {$phone->id}\n";
    echo "    Branch ID: {$phone->branch_id}\n\n";
}