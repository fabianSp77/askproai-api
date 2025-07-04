<?php

/**
 * Fix Phone Number für Krückeberg
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PhoneNumber;
use App\Scopes\TenantScope;

echo "=== Fix Phone Number ===\n\n";

// Alte Nummer aktualisieren
$phoneNumber = PhoneNumber::withoutGlobalScope(TenantScope::class)
    ->where('number', '+493083793369')
    ->where('company_id', 1)
    ->first();

if ($phoneNumber) {
    echo "Aktualisiere Phone Number von {$phoneNumber->number} zu +493033081738...\n";
    
    $phoneNumber->update([
        'number' => '+493033081738'
    ]);
    
    echo "✓ Phone Number aktualisiert!\n";
} else {
    echo "❌ Phone Number nicht gefunden!\n";
}

// Branch auch aktualisieren
$branch = \App\Models\Branch::withoutGlobalScope(TenantScope::class)
    ->where('phone_number', '+493083793369')
    ->where('company_id', 1)
    ->first();

if ($branch) {
    echo "\nAktualisiere Branch Phone Number...\n";
    $branch->update([
        'phone_number' => '+493033081738'
    ]);
    echo "✓ Branch aktualisiert!\n";
}