<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

echo "=== DATENINTEGRITÄTS-CHECK ===\n\n";

// 1. Calls ohne customer_id aber mit Kundendaten
$callsWithoutCustomer = Call::withoutGlobalScopes()
    ->whereNull('customer_id')
    ->where(function($q) {
        $q->whereNotNull('metadata->customer_data->name')
          ->orWhereNotNull('metadata->customer_data->company')
          ->orWhereNotNull('metadata->customer_data->email');
    })
    ->count();
echo "1. Calls ohne customer_id aber mit Kundendaten: " . $callsWithoutCustomer . "\n";

// 2. Doppelte Telefonnummern pro Company
$duplicatePhones = DB::table('customers')
    ->select('phone', 'company_id', DB::raw('COUNT(*) as count'))
    ->whereNotNull('phone')
    ->groupBy('phone', 'company_id')
    ->having('count', '>', 1)
    ->get();
echo "2. Doppelte Telefonnummern: " . $duplicatePhones->count() . "\n";
if ($duplicatePhones->count() > 0) {
    foreach ($duplicatePhones->take(5) as $dup) {
        echo "   - {$dup->phone} (Company {$dup->company_id}): {$dup->count} mal\n";
    }
}

// 3. Calls mit ungültiger customer_id
$validCustomerIds = Customer::withoutGlobalScopes()->pluck('id')->toArray();
$invalidCustomerCalls = Call::withoutGlobalScopes()
    ->whereNotNull('customer_id')
    ->whereNotIn('customer_id', $validCustomerIds)
    ->count();
echo "3. Calls mit ungültiger customer_id: " . $invalidCustomerCalls . "\n";

// 4. Kunden ohne company_id
$customersWithoutCompany = Customer::withoutGlobalScopes()
    ->whereNull('company_id')
    ->count();
echo "4. Kunden ohne company_id: " . $customersWithoutCompany . "\n";

// 5. Telefonnummern-Format Analyse
$phoneFormats = DB::table('customers')
    ->whereNotNull('phone')
    ->select(DB::raw("
        CASE 
            WHEN phone LIKE '+%' THEN 'International (+)'
            WHEN phone LIKE '00%' THEN 'International (00)'
            WHEN phone LIKE '0%' THEN 'National (0)'
            WHEN phone REGEXP '^[1-9]' THEN 'Local (no prefix)'
            ELSE 'Other'
        END as format,
        COUNT(*) as count
    "))
    ->groupBy('format')
    ->get();
echo "5. Telefonnummern-Formate:\n";
foreach ($phoneFormats as $format) {
    echo "   - {$format->format}: {$format->count}\n";
}

// 6. Calls ohne to_number (wichtig für Filterung)
$callsWithoutToNumber = Call::withoutGlobalScopes()
    ->whereNull('to_number')
    ->orWhere('to_number', '')
    ->count();
echo "6. Calls ohne to_number: " . $callsWithoutToNumber . "\n";

// 7. Customer tracking fields Analyse
$customersWithTracking = Customer::withoutGlobalScopes()
    ->where(function($q) {
        $q->whereNotNull('call_count')
          ->orWhereNotNull('last_call_at')
          ->orWhereNotNull('company_name')
          ->orWhereNotNull('customer_number');
    })
    ->count();
$totalCustomers = Customer::withoutGlobalScopes()->count();
echo "7. Kunden mit Tracking-Daten: {$customersWithTracking} von {$totalCustomers} (" . 
     round($customersWithTracking / max($totalCustomers, 1) * 100, 2) . "%)\n";

// 8. Appointments ohne customer_id
$appointmentsWithoutCustomer = Appointment::withoutGlobalScopes()
    ->whereNull('customer_id')
    ->count();
echo "8. Termine ohne customer_id: " . $appointmentsWithoutCustomer . "\n";

// 9. Customer Relationships Analyse
$relationships = DB::table('customer_relationships')->count();
$autoDetected = DB::table('customer_relationships')->where('status', 'auto_detected')->count();
$confirmed = DB::table('customer_relationships')->where('status', 'user_confirmed')->count();
echo "9. Kundenbeziehungen:\n";
echo "   - Gesamt: {$relationships}\n";
echo "   - Automatisch erkannt: {$autoDetected}\n";
echo "   - Bestätigt: {$confirmed}\n";

// 10. Calls mit customer_data aber unterschiedlichen Telefonnummern
$inconsistentPhones = Call::withoutGlobalScopes()
    ->whereNotNull('customer_id')
    ->whereNotNull('metadata->customer_data->phone')
    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.customer_data.phone')) != from_number")
    ->count();
echo "10. Calls mit abweichenden Telefonnummern in customer_data: " . $inconsistentPhones . "\n";

echo "\n=== EMPFEHLUNGEN ===\n";
if ($callsWithoutCustomer > 0) {
    echo "- {$callsWithoutCustomer} Calls haben Kundendaten aber keine customer_id -> Migration empfohlen\n";
}
if ($duplicatePhones->count() > 0) {
    echo "- {$duplicatePhones->count()} doppelte Telefonnummern gefunden -> Merge-Tool empfohlen\n";
}
if ($callsWithoutToNumber > 0) {
    echo "- {$callsWithoutToNumber} Calls ohne to_number -> Kann Filterung beeinträchtigen\n";
}